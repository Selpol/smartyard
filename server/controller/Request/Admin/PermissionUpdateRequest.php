<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор права
 *
 * @property-read string $description Описание права

 */
readonly class PermissionUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'description' => rule()->string()->exist(),
        ];
    }
}