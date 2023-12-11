<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\Internal\AuthMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('/internal', AuthMiddleware::class, config_get('internal.trust', ['127.0.0.1/32']));

    $configurator->psr4('Selpol\\Controller\\Internal\\');
};