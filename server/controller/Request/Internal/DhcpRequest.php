<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string|null $ip IP-Адрес устройства
 * @property-read string|null $mac MAC-Адрес устройства
 * @property-read string|null $host Hostname
 * @property-read string|null $server DHCP Сервер
 */
readonly class DhcpRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ip' => rule()->string(),
            'mac' => rule()->string(),
            'host' => rule()->string(),
            'server' => rule()->string(),
        ];
    }
}