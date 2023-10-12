<?php

namespace Selpol\Controller\Api\task;

use Selpol\Controller\Api\api;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Task\TaskFeature;

class task
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function GET($params): array
    {
        $tasks = container(TaskFeature::class)->page($params['size'], $params['page']);

        return api::ANSWER($tasks, count($tasks) > 0 ? 'tasks' : false);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function POST($params): array
    {
        $result = container(TaskFeature::class)->dispatch($params['_id']);

        return api::ANSWER($result, ($result !== false) ? "task" : false);
    }

    public static function index(): array
    {
        return [
            "GET" => "[Задачи] Получить список",
            "POST" => "[Задачи] Запустить задачу"
        ];
    }
}