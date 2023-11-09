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
        $validate = validator($params, ['_id' => rule()->id()]);

        $monitor = container(MonitorFeature::class);

        return self::success(container(RedisCache::class)->cache('monitor:' . $validate['_id'], static fn() => ['ping' => $monitor->ping($validate['_id']), 'sip' => $monitor->sip($validate['_id'])], 60));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public static function POST(array $params): ResponseInterface
    {
        $validate = validator($params, ['ids.*' => rule()->id()]);

        $result = [];

        $monitor = container(MonitorFeature::class);

        foreach ($validate['ids'] as $id)
            $result[] = container(RedisCache::class)->cache('monitor:' . $id, static fn() => ['ping' => $monitor->ping($id), 'sip' => $monitor->sip($id)], 60);

        return self::success($result);
    }

    public static function index(): array
    {
        return ['GET' => '[Мониторинг] Запросить статус устройства', 'POST' => '[Мониторинг] Запросить статус группы устройств'];
    }
}