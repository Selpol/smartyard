<?php

namespace Selpol\Cache\Trait;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

trait CacheTrait
{
    /**
     * @throws InvalidArgumentException
     */
    public function cache(string $key, callable $default, DateInterval|int|null $ttl = null): mixed
    {
        /** @var CacheInterface $this */

        $value = $this->get($key);

        if ($value !== null)
            return $value;

        $value = call_user_func($default);

        $this->set($key, $value, $ttl);

        return $value;
    }
}