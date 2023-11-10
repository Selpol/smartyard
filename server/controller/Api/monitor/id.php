<?php

namespace Selpol\Controller\Api\monitor;

use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\RedisCache;
use Selpol\Controller\Api\Api;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Monitor\MonitorFeature;

readonly class id extends Api
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     */
    public static function GET(array $params): ResponseInterface
    {
        $id = rule()->id()->onItem('_id', $params);

        return self::success(container(RedisCache::class)->cache('monitor:' . $id, static fn() => container(MonitorFeature::class)->status($id), 60));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public static function POST(array $params): ResponseInterface
    {
        $result = [];

        $monitor = container(MonitorFeature::class);

        foreach ($params['ids'] as $id) {
            $id = rule()->id()->onItem('id', ['id' => $id]);

            $result[$id] = container(RedisCache::class)->cache('monitor:' . $id, static fn() => $monitor->status($id), 60);
        }

        return self::success($result);
    }

    public static function index(): array
    {
        return ['GET' => '[Мониторинг] Запросить статус устройства', 'POST' => '[Мониторинг] Запросить статус группы устройств'];
    }
}