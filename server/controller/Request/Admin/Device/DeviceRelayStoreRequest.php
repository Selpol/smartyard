<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Название устройства
 * @property-read string $url Ссылка на устройство
 * @property-read string $credential Авторизация для устройства
 *
 * @property-read bool $invert Инвентированный выход
 */
readonly class DeviceRelayStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'credential' => rule()->required()->string()->nonNullable(),

            'invert' => rule()->required()->bool()->nonNullable()
        ];
    }
}