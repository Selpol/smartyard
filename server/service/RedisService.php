<?php

namespace Selpol\Service;

use Redis;
use RedisException;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Container\ContainerDisposeInterface;

#[Singleton]
class RedisService implements ContainerDisposeInterface
{
    private ?Redis $redis;

    /**
     * @throws RedisException
     */
    public function __construct()
    {
        $this->redis = new Redis();

        $this->redis->connect(config_get('redis.host'), config_get('redis.port'));

        if (config_get('redis.password'))
            $this->redis->auth(config_get('redis.password'));
    }

    public function getConnection(): ?Redis
    {
        return $this->redis;
    }

    /**
     * @throws RedisException
     */
    function dispose(): void
    {
        $this->redis->close();
        $this->redis = null;
    }
}