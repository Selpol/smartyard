<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Server;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class ServerVariableIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}
