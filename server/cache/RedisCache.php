<?php declare(strict_types=1);

namespace Selpol\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use Selpol\Framework\Cache\Exception\InvalidArgumentException;
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

            $ttl = $timeout->getTimestamp() - $now->getTimestamp();
        }

        if ($ttl) {
            if ($ttl < 0)
                throw new InvalidArgumentException('Внутренняя ошибка');

            return $this->service->setEx('cache:' . $key, $ttl, json_encode($value));
        }

        return $this->service->set('cache:' . $key, json_encode($value));
    }

    public function delete(string $key): bool
    {
        return $this->service->del('cache:' . $key);
    }

    public function clear(): bool
    {
        try {
            $keys = $this->service->keys('cache:*');

            return $this->service->del(...$keys);
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