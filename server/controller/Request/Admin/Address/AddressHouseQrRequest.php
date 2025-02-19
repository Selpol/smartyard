<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор дома
 *
 * @property-read bool $override Перегенерировать коды
 */
readonly class AddressHouseQrRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'override' => [filter()->default(false), rule()->bool()->exist()]
        ];
    }
}