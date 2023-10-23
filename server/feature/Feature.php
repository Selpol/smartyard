<?php

namespace Selpol\Feature;

use Selpol\Service\DatabaseService;
use Selpol\Service\RedisService;

readonly abstract class Feature
{
    protected function getDatabase(): DatabaseService
    {
        return container(DatabaseService::class);
    }

    protected function getRedis(): RedisService
    {
        return container(RedisService::class);
    }
}