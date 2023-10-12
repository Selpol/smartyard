<?php declare(strict_types=1);

use Selpol\Cache\RedisCache;
use Selpol\Framework\Container\ContainerConfigurator;

return static function (ContainerConfigurator $configurator) {
    $configurator->scan('Selpol\\Service\\', path('service/'));
    $configurator->scan('Selpol\\Feature\\', path('feature/'));
    $configurator->scan('Selpol\\Entity\\Repository\\', path('entity/Repository/'));

    $configurator->cache();

    $configurator->singleton(RedisCache::class);
};