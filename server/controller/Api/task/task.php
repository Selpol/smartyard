<?php

namespace Selpol\Controller\Api\task;

use Psr\Http\Message\ResponseInterface;
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
    public static function GET($params): array|ResponseInterface
    {
        $validate = validator($params, [
            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        return self::success(\Selpol\Entity\Model\Task::fetchPage($validate['page'], $validate['size'], criteria()->desc('created_at')));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function POST($params): ResponseInterface
    {
        $result = container(TaskFeature::class)->dispatch($params['_id']);

        if ($result)
            return self::success($params['_id']);

        return self::error('Не удалось перезапустить задачу');
    }

    public static function index(): array
    {
        return ['GET' => '[Задачи] Получить список', 'POST' => '[Задачи] Запустить задачу'];
    }
}