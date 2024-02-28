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
            $this->backupTable($database, $writer, $table);

        foreach (self::SEQUENCES as $sequence)
            $this->backupSequence($database, $writer, $sequence);

        return false;
    }

    public function restore(string $path): bool
    {
        if (!file_exists($path))
            return false;

        return false;
    }

    private function backupTable(DatabaseService $database, InternalBackupWriter $writer, string $table): void
    {
        try {
            $value = $database->get('SELECT column_name FROM information_schema.columns WHERE table_schema = :scheme AND table_name = :table', ['scheme' => 'public', 'table' => $table], options: ['silent']);
            $columns = array_map(static fn(array $item) => $item['column_name'], $value);

            $writer->table($table, $columns);

            $rows = $database->fetch('SELECT ' . implode(', ', $columns) . ' FROM ' . $table, options: ['mode' => PDO::FETCH_NUM]);

            foreach ($rows as $row)
                $writer->row($row);
        } catch (Throwable $throwable) {
            file_logger('backup')->error($throwable, ['table' => $table]);
        }
    }

    private function backupSequence(DatabaseService $database, InternalBackupWriter $writer, string $sequence): void
    {
        try {
            $value = $database->get('SELECT last_value FROM ' . $sequence, options: ['singlify', 'silent']);

            $writer->sequence($sequence, intval($value['last_value']));
        } catch (Throwable $throwable) {
            file_logger('backup')->error($throwable, ['sequence' => $sequence]);
        }
    }
}