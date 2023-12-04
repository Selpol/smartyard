<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $subscriberId
 */
readonly class SubscriberDeleteRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'subscriberId' => rule()->id()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'subscriberId' => 'Идентификатор'
        ];
    }
}