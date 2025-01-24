<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Role;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор роли
 * 
 * @property-read string $title Заголовок
 * @property-read string $description Описание
 */
readonly class RoleUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'title' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()],
            'description' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()]
        ];
    }
}