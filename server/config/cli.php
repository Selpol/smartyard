<?php declare(strict_types=1);

use Selpol\Framework\Cli\CliConfigurator;

return static function (CliConfigurator $configurator) {
    $configurator->psr4('Selpol\\Cli\\');
};