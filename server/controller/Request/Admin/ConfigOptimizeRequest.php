<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read bool $optimize
 */
readonly class ConfigOptimizeRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'optimize' => [filter()->default(false), rule()->required()->bool()->nonNullable()]
        ];
    }
}