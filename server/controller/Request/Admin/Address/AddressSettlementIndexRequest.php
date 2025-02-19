<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read int|null $address_area_id
 * @property-read int|null $address_city_id
 */
readonly class AddressSettlementIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'address_area_id' => rule()->int()->clamp(0),
            'address_city_id' => rule()->int()->clamp(0),
        ];
    }
}