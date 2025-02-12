<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Server;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Название сервера архива
 * @property-read string $type Тип сервера архива. Поддерживается: flunnonic, trassir
 * 
 * @property-read string $url URL к серверу ахрива
 * 
 * @property-read string $token Токен для доступа к архиву
 * @property-read string $credentials Авторизация на сервере архива
 */
readonly class ServerDvrStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->string()->max(1024)->exist(),
            'type' => rule()->string()->in(['flussonic', 'trassir'])->exist(),

            'url' => rule()->url()->exist(),

            'token' => rule()->string()->max(1024)->exist(),
            'credentials' => rule()->string()->max(1024)->exist()
        ];
    }
}
