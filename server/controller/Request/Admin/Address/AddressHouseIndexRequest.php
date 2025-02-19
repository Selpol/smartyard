<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read int|null $address_settlement_id
 * @property-read int|null $address_street_id
 */
readonly class AddressHouseIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'address_settlement_id' => rule()->int()->clamp(0),
            'address_street_id' => rule()->int()->clamp(0),
        ];
    }
}