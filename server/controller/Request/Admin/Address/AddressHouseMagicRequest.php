<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $address
 */
readonly class AddressHouseMagicRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'address' => rule()->string()->clamp(0, 1024)->exist(),
        ];
    }
}