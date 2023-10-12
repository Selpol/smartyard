<?php

namespace Selpol\Controller\Api\monitor;

use Selpol\Controller\Api\api;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Monitor\MonitorFeature;

class id extends api
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public static function GET(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id()]);

        $monitor = container(MonitorFeature::class);

        if ($monitor)
            return api::ANSWER(['ping' => $monitor->ping($validate['_id']), 'sip' => $monitor->sip($validate['_id'])], 'status');

        return api::ERROR('Мониторинг отключен');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function POST(array $params): array
    {
        $validate = validator($params, ['ids.*' => rule()->id()]);

        $result = [];

        $monitor = container(MonitorFeature::class);

        if ($monitor) {
            foreach ($validate['ids'] as $id)
                $result[] = ['ping' => $monitor->ping($id), 'sip' => $monitor->sip($id)];

            return api::ANSWER($result, 'status');
        }

        return api::ERROR('Мониторинг отключен');
    }

    public static function index(): array
    {
        return ['GET' => '[Мониторинг] Запросить статус устройства', 'POST' => '[Мониторинг] Запросить статус группы устройств'];
    }
}