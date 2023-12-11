<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Middleware\PrometheusMiddleware;
use Selpol\Middleware\RateLimitMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('', PrometheusMiddleware::class);

    $configurator->include('/mobile', AuthMiddleware::class);
    $configurator->include('/mobile', SubscriberMiddleware::class);
    $configurator->include('/mobile', RateLimitMiddleware::class, ['trust' => config_get('mobile.trust', ['127.0.0.1/32']), 'count' => 60, 'ttl' => 60]);

    $configurator->psr4('Selpol\\Controller\\Mobile\\');
};