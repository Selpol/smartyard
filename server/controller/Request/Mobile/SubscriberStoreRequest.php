<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $mobile
 */
readonly class SubscriberStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'mobile' => rule()->required()->int()->min(70000000000)->max(79999999999)->nonNullable()
        ];
    }
}