<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Key;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $rfid RFID-Метка
 * 
 * @property-read int $access_type Тип доступа 2 - квартира
 * @property-read int $access_to Куда доступ
 *
 * @property-read string|null $comments Комментарий
 */
readonly class KeyStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'rfid' => rule()->required()->string()->nonNullable(),

            'access_type' => rule()->required()->in([0, 1, 2, 3, 4])->nonNullable(),
            'access_to' => rule()->id(),

            'comments' => rule()->string()
        ];
    }
}