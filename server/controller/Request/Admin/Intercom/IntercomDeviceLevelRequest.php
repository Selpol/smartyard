<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read int|null $apartment Квартира
 * 
 * @property-read int|null $from Первая квартира
 * @property-read int|null $ti Последняя квартира
 * 
 * @property-read bool $info Дополнительная информация
 */
readonly class IntercomDeviceLevelRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'apartment' => rule()->int(),

            'from' => rule()->int(),
            'to' => rule()->int(),

            'info' => [filter()->default(false), rule()->bool()->exist()]
        ];
    }
}