<?php

namespace Selpol\Service;

use Psr\Log\LoggerAwareInterface;
use Redis;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Throwable;

#[Singleton]
class RedisService implements LoggerAwareInterface
{
    use LoggerKernelTrait;

    private ?Redis $redis;

    public function __construct()
    {
        $this->logger = file_logger('redis');

        try {
            $redis = new Redis();
            $redis->connect(config_get('redis.host'), config_get('redis.port'));

            if ($password = config_get('redis.password')) {
                $redis->auth($password);
            }

            $this->redis = $redis;
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage() . PHP_EOL . $throwable);

            $this->redis = null;
        }
    }

    public function getConnection(): ?Redis
    {
        return $this->redis;
    }

    public function database(int $index): bool
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->select($index);
        } catch (Throwable) {
            return false;
        }
    }

    public function use(int $index, callable $callback): mixed
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

            $database = $this->redis->getDbNum();

            if ($database === false) {
                $database = 0;
            }
        } catch (Throwable) {
            $database = 0;
        }

        if ($database == $index) {
            return call_user_func($callback, $this);
        } else if ($this->database($index)) {
            try {
                return call_user_func($callback, $this);
            } finally {
                $this->database($database);
            }
        }

        return false;
    }

    public function keys(string $pattern): array
    {
        try {
            if (is_null($this->redis)) {
                return [];
            }

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
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->get($key);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function set(string $key, mixed $value, mixed $options = null): bool
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->set($key, $value, $options);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function setEx(string $key, int $expire, mixed $value): bool
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->setex($key, $expire, $value);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function setNx(string $key, mixed $value): bool
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->setnx($key, $value);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function exist(string $key, array ...$keys): bool
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

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
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->del($key, ...$keys) > 0;
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }

    public function eval(string $script, array $args = [], int $numKeys = 0): mixed
    {
        try {
            if (is_null($this->redis)) {
                return false;
            }

            return $this->redis->eval($script, $args, $numKeys);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            return false;
        }
    }
}