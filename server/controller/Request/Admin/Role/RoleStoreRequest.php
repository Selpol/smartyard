<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Role;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Заголовок
 * @property-read string $description Описание
 */
readonly class RoleStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()],
            'description' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()]
        ];
    }
}