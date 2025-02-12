<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Server;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор стримера
 * 
 * @property-read string $title Название стримера
 * 
 * @property-read string $url URL к стримеру
 */
readonly class ServerStreamerUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'title' => rule()->string()->max(1024)->exist(),
            'url' => rule()->string()->url()->exist()
        ];
    }
}
