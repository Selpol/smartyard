<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read null|string $title Название устройства
 * @property-read null|string $url Ссылка на устройство
 * @property-read null|string $credential Авторизация для устройства
 *
 * @property-read null|bool $invert Инвентированный выход
 */
readonly class DeviceRelayUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'title' => rule()->string(),
            'url' => rule()->url(),
            'credential' => rule()->string(),

            'invert' => rule()->bool()
        ];
    }
}