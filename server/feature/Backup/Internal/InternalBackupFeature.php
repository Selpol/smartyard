<?php declare(strict_types=1);

namespace Selpol\Feature\Backup\Internal;

use PDO;
use Selpol\Feature\Backup\BackupFeature;
use Selpol\Service\DatabaseService;
use Throwable;

readonly class InternalBackupFeature extends BackupFeature
{
    public function backup(string $path): bool
    {
        if (file_exists($path))
            return false;

        $database = container(DatabaseService::class);
        $writer = new InternalBackupWriter($path);

        foreach (self::TABLES as $table)
            try {
                $this->backupTable($database, $writer, $table);
            } catch (Throwable $throwable) {
                file_logger('backup')->error($throwable, ['table' => $table, 'path' => $path]);
            }

        foreach (self::SEQUENCES as $sequence)
            try {
                $this->backupSequence($database, $writer, $sequence);
            } catch (Throwable $throwable) {
                file_logger('backup')->error($throwable, ['sequence' => $sequence, 'path' => $path]);
            }

        return false;
    }

    public function restore(string $path): bool
    {
        if (!file_exists($path))
            return false;

        $database = container(DatabaseService::class);
        $reader = new InternalBackupReader($path);

        try {
            while ($section = $reader->section()) {
                if ($section[0] === 'TABLE')
                    try {
                        $this->restoreTable($database, $reader, $section[1], $section[2]);
                    } catch (Throwable $throwable) {
                        file_logger('backup')->error($throwable, ['section' => $section, 'path' => $path]);
                    }
                else if ($section[0] === 'SEQUENCE')
                    try {
                        $this->restoreSequence($database, $section[1], $section[2]);
                    } catch (Throwable $throwable) {
                        file_logger('backup')->error($throwable, ['section' => $section, 'path' => $path]);
                    }
            }
        } catch (Throwable $throwable) {
            file_logger('backup')->error($throwable, ['path' => $path]);
        }

        return false;
    }

    private function backupTable(DatabaseService $database, InternalBackupWriter $writer, string $table): void
    {
        $value = $database->get('SELECT column_name FROM information_schema.columns WHERE table_schema = :scheme AND table_name = :table', ['scheme' => 'public', 'table' => $table], options: ['silent']);
        $columns = array_map(static fn(array $item) => $item['column_name'], $value);

        $writer->table($table, $columns);

        $rows = $database->fetch('SELECT ' . implode(', ', $columns) . ' FROM ' . $table, options: ['mode' => PDO::FETCH_NUM]);

        foreach ($rows as $row)
            $writer->row($row);

        $writer->section();
    }

    private function backupSequence(DatabaseService $database, InternalBackupWriter $writer, string $sequence): void
    {
        $value = $database->get('SELECT last_value FROM ' . $sequence, options: ['singlify', 'silent']);

        $writer->sequence($sequence, intval($value['last_value']));

        $writer->section();
    }

    /**
     * @param DatabaseService $database
     * @param InternalBackupReader $reader
     * @param string $table
     * @param string[] $columns
     * @return void
     */
    private function restoreTable(DatabaseService $database, InternalBackupReader $reader, string $table, array $columns): void
    {
        $database->modify('TRUNCATE ' . $table . ' CASCADE');

        $query = 'INSERT INTO ' . $table . '(' . implode(', ', $columns) . ') VALUES (';

        while ($row = $reader->row()) {
            $rowQuery = $query;

            $rowQuery .= implode(', ', array_map(static function (mixed $column) {
                if (is_string($column))
                    return '\'' . $column . '\'';
                else if (is_bool($column))
                    return $column ? 'true' : 'false';
                else if (is_null($column))
                    return 'null';

                return $column;
            }, $row));

            $rowQuery .= ')';

            $database->insert($rowQuery);
        }
    }

    private function restoreSequence(DatabaseService $database, string $sequence, int $value): void
    {
        $database->modify('SELECT SETVAL(\'' . $sequence . '\', :value)', ['value' => $value]);
    }
}