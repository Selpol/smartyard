<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор DVR сервера
 *
 * @property-read string $camera Идентификатор камеры на DVR сервере
 */
readonly class DvrCameraShowRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'camera' => rule()->required()->string()->nonNullable(),
        ];
    }
}