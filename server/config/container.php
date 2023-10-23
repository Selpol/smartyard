<?php declare(strict_types=1);

use Selpol\Cache\RedisCache;
use Selpol\Framework\Container\ContainerConfigurator;
use Selpol\Service\Database\EntityConnection;

return static function (ContainerConfigurator $configurator) {
    $configurator->fileCache();

    $configurator->entity(EntityConnection::class);

    $configurator->scan('Selpol\\Service\\', path('service/'));
    $configurator->scan('Selpol\\Feature\\', path('feature/'));
    $configurator->scan('Selpol\\Entity\\Repository\\', path('entity/Repository/'));

    $configurator->singleton(RedisCache::class);
};