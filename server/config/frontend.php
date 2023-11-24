<?php declare(strict_types=1);

use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Middleware\RateLimitMiddleware;

return static function (RouterConfigurator $configurator) {
    $configurator->include('/frontend', RateLimitMiddleware::class, ['trust' => config_get('mobile.trust', ['127.0.0.1/32']), 'count' => 60, 'ttl' => 60]);

    $configurator->psr4('Selpol\\Controller\\Frontend\\');
};