<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|string $file
 */
readonly class LogIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'file' => rule()->string()->max(1024),
        ];
    }
}