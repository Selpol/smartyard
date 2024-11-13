<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $type
 */
readonly class ConfigIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'type' => rule()->required()->string()->in(['intercom'])->nonNullable(),
        ];
    }
}