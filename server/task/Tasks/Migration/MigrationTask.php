<?php

namespace Selpol\Task\Tasks\Migration;

use Selpol\Task\Task;

abstract class MigrationTask extends Task
{
    public int $dbVersion;
    public ?int $version;

    public function __construct(string $title, int $dbVersion, ?int $version)
    {
        parent::__construct($title);

        $this->dbVersion = $dbVersion;
        $this->version = $version;
    }

    protected function getMigration(string $path, ?int $min = null, ?int $max = null): array
    {
        $files = scandir(path('migration/pgsql/' . $path . '/'));

        return array_reduce($files, static function (array $previous, string $file) use ($min, $max) {
            if (!str_starts_with($file, 'v') || !str_ends_with($file, '.sql'))
                return $previous;

            $segments = explode('_', $file);

            if (count($segments) < 3)
                return $previous;

            $version = (int)substr($segments[0], 1);
            $step = (int)$segments[1];

            if ($min && $version <= $min)
                return $previous;

            if ($max && $version >= $max)
                return $previous;

            if (!array_key_exists($version, $previous))
                $previous[$version] = [];

            $previous[$version][$step] = $file;

            return $previous;
        }, []);
    }
}