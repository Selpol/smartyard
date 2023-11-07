<?php

namespace Selpol\Service;

use Psr\Log\LoggerAwareInterface;
use Redis;
use RedisException;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Container\ContainerDisposeInterface;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Throwable;

#[Singleton]
class RedisService implements ContainerDisposeInterface, LoggerAwareInterface
{
    use LoggerKernelTrait;

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

        $this->logger = file_logger('redis');
    }

    public function getConnection(): ?Redis
    {
        return $this->redis;
    }

    /**
     * @param string $key
     * @return false|mixed
     */
    public function get(string $key): mixed
    {
        try {
            return $this->getConnection()->get($key);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function setEx(string $key, int $expire, mixed $value): bool
    {
        try {
            return $this->getConnection()->setex($key, $expire, $value);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function exist(string $key, array ...$keys): bool
    {
        try {
            $exist = $this->getConnection()->exists($key, ...$keys);

            return $exist === true || $exist > 0;
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function del(string $key, array ...$keys): bool
    {
        try {
            return $this->getConnection()->del($key, ...$keys) > 0;
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    /**
     * @throws RedisException
     */
    public function dispose(): void
    {
        $this->redis->close();
        $this->redis = null;
    }
}