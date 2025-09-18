<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор дома
 * @property-read string $password Пароль пользователя
 */
readonly class HouseDeleteRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'password' => rule()->string()->exist()
        ];
    }
}