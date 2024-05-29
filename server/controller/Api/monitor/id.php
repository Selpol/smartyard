<?php

namespace Selpol\Controller\Api\monitor;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Controller\Api\Api;
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
        $validate = validator($params, [
            '_id' => rule()->id(),

            'type' => rule()->in(['ping', 'sip'])
        ]);

        $monitor = container(MonitorFeature::class);

        return self::success((!$validate['type'] || $validate['type'] === 'ping') ? $monitor->ping($validate['_id']) : $monitor->sip($validate['_id']));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public static function POST(array $params): ResponseInterface
    {
        $type = rule()->in(['ping', 'sip'])->onItem('type', $params);

        $result = [];

        $monitor = container(MonitorFeature::class);

        foreach ($params['ids'] as $id) {
            $id = rule()->id()->onItem('id', ['id' => $id]);

            $result[$id] = (!$type || $type === 'ping') ? $monitor->ping($id) : $monitor->sip($id);
        }

        return self::success($result);
    }

    public static function index(): array
    {
        return ['GET' => '[Мониторинг] Запросить статус устройства', 'POST' => '[Мониторинг] Запросить статус группы устройств'];
    }
}