<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Server;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Название стримера
 * 
 * @property-read string $url URL к стримеру
 */
readonly class ServerStreamerFrsStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->string()->max(1024)->exist(),
            'url' => rule()->string()->url()->exist()
        ];
    }
}
