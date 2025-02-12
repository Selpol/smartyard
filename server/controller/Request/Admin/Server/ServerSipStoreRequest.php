<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Server;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Название сипа
 * 
 * @property-read string $type Тип сипа. Поддерживается: asterisk
 * 
 * @property-read string $trunk Транк
 * 
 * @property-read string $external_ip IP для абонентов
 * @property-read string $internal_ip IP для домофонов
 * 
 * @property-read int $external_port Порт для абонентов
 * @property-read int $internal_port Порт для домофонов
 */
readonly class ServerSipStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->string()->max(1024)->exist(),
            'type' => rule()->string()->in(['asterisk'])->exist(),

            'trunk' => rule()->string()->exist(),

            'external_ip' => rule()->ipV4()->exist(),
            'internal_ip' => rule()->ipV4()->exist(),

            'external_port' => rule()->int()->clamp(0, 65535)->exist(),
            'internal_port' => rule()->int()->clamp(0, 65535)->exist()
        ];
    }
}
