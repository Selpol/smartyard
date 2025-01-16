<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Название
 * @property-read int $flat Квартира
 * @property-read string|null $code Код открытия
 */
readonly class ContractStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->required()->string()->clamp(0, 1000)->nonNullable(),
            'flat' => rule()->required()->int()->clamp(0, 10000)->nonNullable(),
            'code' => rule()->string()->clamp(5, 5)
        ];
    }
}