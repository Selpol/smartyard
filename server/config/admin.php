<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\Admin\AuthMiddleware;
use Selpol\Middleware\Admin\ScopeMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('/admin', AuthMiddleware::class);
    $configurator->include('/admin', ScopeMiddleware::class);

    $configurator->psr4('Selpol\\Controller\\Admin\\');
};