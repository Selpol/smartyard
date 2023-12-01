<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $flatId
 *
 * @property-read string $events
 */
readonly class PlogDaysRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'flatId' => rule()->id(),
            'events' => rule()->string()->clamp(0, max: 64)
        ];
    }
}