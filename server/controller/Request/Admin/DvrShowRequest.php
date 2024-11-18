<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор DVR сервера
 *
 * @property-read string $search Строка поиска камеры
 */
readonly class DvrShowRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'search' => rule()->required()->string()->nonNullable(),
        ];
    }
}