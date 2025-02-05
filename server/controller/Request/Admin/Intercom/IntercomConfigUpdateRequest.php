<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read string $key Ключ
 * @property-read string $value Значение
 */
readonly class IntercomConfigUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'key' => rule()->required()->string()->nonNullable(),
            'value' => rule()->required()->string()->nonNullable()
        ];
    }
}