<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read int|null $address_region_id
 */
readonly class AddressAreaIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'address_region_id' => rule()->int()->clamp(0),
        ];
    }
}