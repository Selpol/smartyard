<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $search
 * @property-read null|string $bound
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