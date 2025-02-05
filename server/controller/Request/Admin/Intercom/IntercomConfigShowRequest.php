<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read string $key Ключ значения
 */
readonly class IntercomConfigShowRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'key' => rule()->required()->string()->nonNullable()
        ];
    }
}