<?php

namespace Selpol\Task\Tasks\Migration;

use Selpol\Task\Task;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

abstract class MigrationTask extends Task implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public $taskUniqueIgnore = ['dbVersion', 'version'];

    public int $dbVersion;
    public ?int $version;
    public bool $force;

    public function __construct(string $title, int $dbVersion, ?int $version, bool $force)
    {
        parent::__construct($title);

        $this->dbVersion = $dbVersion;
        $this->version = $version;
        $this->force = $force;
    }

    protected function getMigration(string $path, ?int $min = null, ?int $max = null): array
    {
        $files = scandir(path('migration/pgsql/' . $path . '/'));

        $result = array_reduce($files, static function (array $previous, string $file) use ($min, $max) {
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

        ksort($result);

        return $result;
    }
}