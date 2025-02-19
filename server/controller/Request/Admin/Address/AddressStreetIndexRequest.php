<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read int|null $address_city_id
 * @property-read int|null $address_settlement_id
 */
readonly class AddressStreetIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'address_city_id' => rule()->int()->clamp(0),
            'address_settlement_id' => rule()->int()->clamp(0),
        ];
    }
}