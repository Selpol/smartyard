<?php

namespace api\monitor;

use api\api;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Validator\Rule;

class id extends api
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public static function GET($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()]]);

        $monitor = backend('monitor');

        if ($monitor)
            return api::ANSWER(['ping' => $monitor->ping($validate['_id']), 'sip' => $monitor->sip($validate['_id'])], 'status');

        return api::ERROR('Мониторинг отключен');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function POST($params)
    {
        $ids = $params['ids'];

        $validate = validator($ids, array_reduce(array_keys($ids), static function (array $previous, string|int $current) {
            $previous[$current] = [Rule::id()];

            return $previous;
        }, []));

        $result = [];

        $monitor = backend('monitor');

        if ($monitor) {
            foreach ($validate as $id)
                $result[] = ['ping' => $monitor->ping($id), 'sip' => $monitor->sip($id)];

            return api::ANSWER($result, 'status');
        }

        return api::ERROR('Мониторинг отключен');
    }

    public static function index(): array
    {
        return ['GET' => '#same(monitor,id,GET)', 'POST' => '#same(monitor,id,POST)'];
    }
}