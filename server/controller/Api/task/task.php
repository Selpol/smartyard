<?php

namespace Selpol\Controller\Api\task;

use Selpol\Controller\Api\Api;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Task\TaskFeature;

readonly class task extends Api
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function GET($params): array
    {
        $validate = validator($params, [
            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        return Api::ANSWER(\Selpol\Entity\Model\Task::fetchPage($validate['page'], $validate['size'], criteria()->desc('created_at')), 'tasks');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function POST($params): array
    {
        $result = container(TaskFeature::class)->dispatch($params['_id']);

        return Api::ANSWER($result, ($result !== false) ? "task" : false);
    }

    public static function index(): array
    {
        return ['GET' => '[Задачи] Получить список', 'POST' => '[Задачи] Запустить задачу'];
    }
}