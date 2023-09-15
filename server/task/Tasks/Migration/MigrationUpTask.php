<?php

namespace Selpol\Task\Tasks\Migration;

use RuntimeException;
use Selpol\Service\DatabaseService;
use Throwable;

class MigrationUpTask extends MigrationTask
{
    public function __construct(int $dbVersion, ?int $version)
    {
        parent::__construct('Повышение версии базы данных (' . $dbVersion . ', ' . $version . ')', $dbVersion, $version);
    }

    public function onTask(): bool
    {
        if ($this->version && $this->dbVersion >= $this->version)
            return false;

        $migrations = $this->getMigration('up');

        $db = container(DatabaseService::class);

        $db->beginTransaction();

        foreach ($migrations as $migrationVersion => $migrationValues) {
            if ($migrationVersion > $this->dbVersion && ($this->version === null || $migrationVersion >= $this->version)) {
                try {
                    foreach ($migrationValues as $migrationStep) {
                        $sql = trim(file_get_contents(path('migration/pgsql/up/' . $migrationStep)));

                        $db->exec($sql);
                    }
                } catch (Throwable $throwable) {
                    $db->rollBack();

                    throw new RuntimeException($throwable->getMessage(), previous: $throwable);
                }
            }

            $db->modify("UPDATE core_vars SET var_value = :version WHERE var_name = 'dbVersion'", ['version' => $migrationVersion]);
        }

        return $db->commit();
    }
}