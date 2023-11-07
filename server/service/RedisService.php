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

    public function keys(string $pattern): array
    {
        try {
            return $this->redis->keys($pattern);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return [];
        }
    }

    /**
     * @param string $key
     * @return false|mixed
     */
    public function get(string $key): mixed
    {
        try {
            return $this->redis->get($key);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function set(string $key, mixed $value, mixed $options = null): bool
    {
        try {
            return $this->redis->set($key, $value, $options);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function setEx(string $key, int $expire, mixed $value): bool
    {
        try {
            return $this->redis->setex($key, $expire, $value);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function exist(string $key, array ...$keys): bool
    {
        try {
            $exist = $this->redis->exists($key, ...$keys);

            return $exist === true || $exist > 0;
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function del(string $key, array ...$keys): bool
    {
        try {
            return $this->redis->del($key, ...$keys) > 0;
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