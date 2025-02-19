<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read int|null $address_region_id
 * @property-read int|null $address_area_id
 */
readonly class AddressCityIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'address_region_id' => rule()->int()->clamp(0),
            'address_area_id' => rule()->int()->clamp(0),
        ];
    }
}