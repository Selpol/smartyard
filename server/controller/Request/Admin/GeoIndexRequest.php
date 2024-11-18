<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $search Строка поиска
 * @property-read null|string $bound Ограничение поиска
 */
readonly class GeoIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'search' => rule()->required()->string()->nonNullable(),
            'bound' => rule()->string()
        ];
    }
}