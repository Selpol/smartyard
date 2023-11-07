<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\InternalMiddleware;
use Selpol\Middleware\JwtMiddleware;
use Selpol\Middleware\MobileMiddleware;
use Selpol\Middleware\PrometheusMiddleware;
use Selpol\Middleware\RateLimitMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('', PrometheusMiddleware::class);

    $configurator->include('/internal', InternalMiddleware::class, config_get('internal.trust', ['127.0.0.1/32']));

    $configurator->include('/mobile', JwtMiddleware::class);
    $configurator->include('/mobile', MobileMiddleware::class);
    $configurator->include('/mobile', RateLimitMiddleware::class, ['trust' => config_get('mobile.trust', ['127.0.0.1/32']), 'count' => 60, 'ttl' => 60]);

    $configurator->psr4('Selpol\\Controller\\Internal\\');
    $configurator->psr4('Selpol\\Controller\\Mobile\\');
};