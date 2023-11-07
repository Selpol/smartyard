<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $date;
 * @property-read string $ip
 *
 * @property-read null|int $callId
 */
readonly class ActionCallFinishedRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'date' => rule()->required()->int()->nonNullable(),
            'ip' => rule()->required()->ipV4()->nonNullable(),

            'callId' => [filter()->default(0), rule()->int()]
        ];
    }
}