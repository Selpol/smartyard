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
        container(TaskFeature::class)->clearUnique();

        return self::success();
    }

    public static function index(): array
    {
        return ['GET' => '[Задачи] Получить список уникальных задач', 'DELETE' => '[Задачи] Очистить список уникальных задач'];
    }
}