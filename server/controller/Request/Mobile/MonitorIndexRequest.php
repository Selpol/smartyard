<?php

declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int[] $ids
 */
readonly class MonitorIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ids' => rule()->array()->exist(),
            'ids.*' => rule()->id()
        ];
    }
}
