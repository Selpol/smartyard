<?php declare(strict_types=1);

namespace Selpol\Service\Redis;

use Selpol\Service\RedisService;

/**
 * @mixin \Selpol\Service\RedisService
 */
class RedisUsable
{
    private RedisService $service;

    private int $index;

    public function __construct(RedisService $service, int $index)
    {
        $this->service = $service;

        $this->index = $index;
    }

    public function __call($method, $args)
    {
        return $this->service->use($this->index, static fn(RedisService $service) => $service->{$method}(...$args));
    }
}
