<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string|null $title Название
 * @property-read int|null $flat Квартира
 * 
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class ContractIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->string()->clamp(0, 1000),
            'flat' => rule()->int()->clamp(0, 10000),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}