<?php

namespace Selpol\Controller\Api\task;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Http\Response;

readonly class unique extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        return self::success(container(TaskFeature::class)->getUniques());
    }

    public static function DELETE(array $params): array|Response|ResponseInterface
    {
        $key = rule()->required()->string()->nonNullable()->onItem('key', $params);

        if (strlen($key) > 12) {
            container(TaskFeature::class)->releaseUnique(substr($key, 12));

            return self::success();
        }

        return self::error('Не верный ключ');
    }

    public static function index(): array
    {
        return ['GET' => '[Задачи] Получить список уникальных задач', 'DELETE' => '[Задачи] Очистить список уникальных задач'];
    }
}