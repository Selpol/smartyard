<?php

use Selpol\Cache\FileCache;
use Selpol\Cache\RedisCache;
use Selpol\Container\ContainerConfigurator;
use Selpol\Service\AuthService;
use Selpol\Service\BackendService;
use Selpol\Service\ClientService;
use Selpol\Service\DatabaseService;
use Selpol\Service\DeviceService;
use Selpol\Service\FrsService;
use Selpol\Service\HttpService;
use Selpol\Service\RedisService;
use Selpol\Service\TaskService;

return static function (ContainerConfigurator $builder) {
    $builder->singleton(RedisService::class);
    $builder->singleton(DatabaseService::class);
    $builder->singleton(TaskService::class);

    $builder->singleton(HttpService::class);
    $builder->singleton(ClientService::class);

    $builder->singleton(DeviceService::class);

    $builder->singleton(BackendService::class);

    $builder->singleton(AuthService::class);

    $builder->singleton(FileCache::class);
    $builder->singleton(RedisCache::class);

    $builder->singleton(FrsService::class);
};