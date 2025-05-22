<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Contract;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор подрядчика
 *
 * @property-read string $title Название
 * @property-read int $flat Квартира
 * @property-read int $flat_flag Флаги квартиры
 * @property-read string|null $code Код открытия
 */
readonly class ContractUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'title' => rule()->string()->clamp(0, 1000)->exist(),

            'flat' => rule()->int()->clamp(0, 10000)->exist(),
            'flat_flag' => rule()->int()->min()->nonNullable(),

            'code' => rule()->string()->clamp(5, 5)
        ];
    }
}