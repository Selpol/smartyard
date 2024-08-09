<?php

namespace Selpol\Controller\Api\task;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
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
            'title' => rule()->string(),
            'message' => rule()->string(),

            'class' => rule()->string(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $criteria = criteria()->like('title', $validate['title'])->like('message', $validate['message'])->equal('class', $validate['class'])->desc('created_at');

        return self::success(\Selpol\Entity\Model\Task::fetchPage($validate['page'], $validate['size'], $criteria));
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

        return self::error('Не удалось перезапустить задачу', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Задачи] Получить список', 'POST' => '[Задачи] Запустить задачу'];
    }
}