<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read null|int $type
 * @property-read null|bool $opened
 *
 * @property-read int $page
 * @property-read int $size
 */
readonly class PlogIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'type' => rule()->int(),
            'opened' => rule()->bool(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}