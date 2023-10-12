<?php

namespace Selpol\Feature;

use Selpol\Service\Database\Manager;
use Selpol\Service\DatabaseService;
use Selpol\Service\RedisService;

abstract class Feature
{
    protected function getDatabase(): DatabaseService
    {
        return container(DatabaseService::class);
    }

    protected function getManager(): Manager
    {
        return $this->getDatabase()->getManager();
    }

    protected function getRedis(): RedisService
    {
        return container(RedisService::class);
    }
}