<?php

namespace Selpol\Feature;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DatabaseService;
use Selpol\Service\RedisService;

abstract class Feature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    protected function getDatabase(): DatabaseService
    {
        return container(DatabaseService::class);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    protected function getRedis(): RedisService
    {
        return container(RedisService::class);
    }
}