<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\InternalMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('/internal', InternalMiddleware::class, config_get('internal.trust', ['127.0.0.1/32']));

    $configurator->psr4('Selpol\\Controller\\Internal\\');
};