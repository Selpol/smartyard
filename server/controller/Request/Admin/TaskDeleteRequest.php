<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $key Ключ для удаления
 */
readonly class TaskDeleteRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'key' => rule()->required()->string()->nonNullable()
        ];
    }
}