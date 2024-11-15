<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $uuid
 */
readonly class PlogCamshotRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'uuid' => rule()->required()->nonNullable()
        ];
    }
}