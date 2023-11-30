<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read string $from
 * @property-read string $to
 */
readonly class ArchivePrepareRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'from' => rule()->required()->nonNullable(),
            'to' => rule()->required()->nonNullable()
        ];
    }
}