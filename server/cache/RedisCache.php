<?php declare(strict_types=1);

namespace Selpol\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Selpol\Cache\Trait\CacheTrait;
use Selpol\Container\Container;
use Selpol\Service\RedisService;
use Throwable;

class RedisCache implements CacheInterface
{
    use CacheTrait;

    private RedisService $service;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Container $container)
    {
        $this->service = $container->get(RedisService::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->service->getConnection()->get('cache:' . $key);

        if ($value === false)
            return $default;

        return $value;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $timeout = $now->add($ttl);

            return $this->service->getConnection()->set('cache:' . $key, $value, $timeout->getTimestamp() - $now->getTimestamp());
        }

        return $this->service->getConnection()->set('cache:' . $key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->service->getConnection()->del('cache:' . $key) === 1;
    }

    public function clear(): bool
    {
        try {
            $keys = $this->service->getConnection()->keys('cache:*');

            if (count($keys) > 0)
                $this->service->getConnection()->del($keys) > 0;

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
        return $this->service->getConnection()->exists($key) !== false;
    }
}