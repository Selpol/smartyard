<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор стримера
 *
 * @property-read string $stream_id Идентификатор потока
 */
readonly class StreamerDeleteRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'stream_id' => rule()->required()->string()->nonNullable()
        ];
    }
}