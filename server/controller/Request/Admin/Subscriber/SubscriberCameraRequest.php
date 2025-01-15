<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $subscriber_id Идентификатор абонента
 * @property-read int $camera_id Идентификатор камеры
 */
readonly class SubscriberCameraRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'subscriber_id' => rule()->id(),
            'camera_id' => rule()->id(),
        ];
    }
}