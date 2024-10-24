<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Middleware\PrometheusMiddleware;
use Selpol\Middleware\RateLimitMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('/mobile', PrometheusMiddleware::class);
    $configurator->include('/mobile', AuthMiddleware::class, ['user' => config_get('mobile.user', false)]);
    $configurator->include('/mobile', SubscriberMiddleware::class);

    if (config_get('mobile.rate_limit.enable', false)) {
        $configurator->include('/mobile', RateLimitMiddleware::class, ['trust' => config_get('mobile.rate_limit.trust', ['127.0.0.1/32']), 'count' => 120, 'ttl' => 30, 'null' => config_get('mobile.rate_limit.null', false)]);
    }

    $configurator->psr4('Selpol\\Controller\\Mobile\\');
};