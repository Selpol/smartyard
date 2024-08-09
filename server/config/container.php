<?php declare(strict_types=1);

use Selpol\Cache\RedisCache;
use Selpol\Framework\Cache\FileCache;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Container\ContainerConfigurator;
use Selpol\Service\Database\PDOEntityConnection;

return static function (ContainerConfigurator $configurator) {
    $configurator->entity(PDOEntityConnection::class);

    $configurator->singleton(FileCache::class);
    $configurator->singleton(RedisCache::class);

    $configurator->singleton(Client::class);

    $configurator->psr4('Selpol\\Service\\');
    $configurator->psr4('Selpol\\Feature\\');

    $configurator->psr4('Selpol\\Entity\\Repository\\');
};