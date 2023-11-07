<?php declare(strict_types=1);

namespace Selpol\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use RedisException;
use Selpol\Framework\Cache\Trait\CacheTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\RedisService;
use Throwable;

#[Singleton]
readonly class RedisCache implements CacheInterface
{
    use CacheTrait;

    private RedisService $service;

    public function __construct()
    {
        $this->service = container(RedisService::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->service->get('cache:' . $key);

        if ($value === false)
            return $default;

        return json_decode($value, true);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $timeout = $now->add($ttl);

            return $this->service->setEx('cache:' . $key, $timeout->getTimestamp() - $now->getTimestamp(), json_encode($value));
        }

        return $this->service->setEx('cache:' . $key, $ttl, json_encode($value));
    }

    public function delete(string $key): bool
    {
        return $this->service->del('cache:' . $key);
    }

    public function clear(): bool
    {
        try {
            $keys = $this->service->getConnection()->keys('cache:*');

            if (count($keys) > 0)
                $this->service->del(...$keys) > 0;

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key)
            yield $this->get($key, $default);
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        try {
            foreach ($values as $key => $value)
                $this->set($key, $value, $ttl);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        try {
            foreach ($keys as $key)
                $this->delete($key);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        $result = $this->service->exist($key);

        return $result !== false && $result > 0;
    }
}