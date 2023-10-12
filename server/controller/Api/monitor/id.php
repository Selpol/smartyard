<?php

namespace Selpol\Controller\Api\monitor;

use Selpol\Controller\Api\Api;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Monitor\MonitorFeature;

class id extends Api
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
            return Api::ANSWER(['ping' => $monitor->ping($validate['_id']), 'sip' => $monitor->sip($validate['_id'])], 'status');

        return Api::ERROR('Мониторинг отключен');
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

            return Api::ANSWER($result, 'status');
        }

        return Api::ERROR('Мониторинг отключен');
    }

    public static function index(): array
    {
        return ['GET' => '[Мониторинг] Запросить статус устройства', 'POST' => '[Мониторинг] Запросить статус группы устройств'];
    }
}