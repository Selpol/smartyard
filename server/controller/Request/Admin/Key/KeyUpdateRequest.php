<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Key;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор ключа
 * 
 * @property-read null|string $comments Комментарий
 */
readonly class KeyUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'comments' => rule()->string()
        ];
    }
}