<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;

return static function (RouterConfigurator $configurator) {
    $configurator->psr4('Selpol\\Controller\\Internal\\');
};