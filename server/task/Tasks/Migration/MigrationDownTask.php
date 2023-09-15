<?php

namespace Selpol\Task\Tasks\Migration;

use RuntimeException;
use Selpol\Service\DatabaseService;
use Throwable;

class MigrationDownTask extends MigrationTask
{
    public function __construct(int $dbVersion, ?int $version)
    {
        parent::__construct('Понижение версии базы данных (' . $dbVersion . ', ' . $version . ')', $dbVersion, $version);
    }

    public function onTask(): bool
    {
        if ($this->version && $this->dbVersion <= $this->version)
            return true;

        $migrations = array_reverse($this->getMigration('down', $this->version, $this->dbVersion + 1), true);

        $db = container(DatabaseService::class);

        $db->beginTransaction();

        foreach ($migrations as $migrationVersion => $migrationValues) {
            try {
                $migrationValues = array_reverse($migrationValues, true);

                foreach ($migrationValues as $migrationStep) {
                    $sql = trim(file_get_contents(path('migration/pgsql/down/' . $migrationStep)));

                    $db->exec($sql);
                }
            } catch (Throwable $throwable) {
                $db->rollBack();

                throw new RuntimeException($throwable->getMessage(), previous: $throwable);
            }

            $db->modify("UPDATE core_vars SET var_value = :version WHERE var_name = 'dbVersion'", ['version' => $migrationVersion - 1]);
        }

        return $db->commit();
    }
}