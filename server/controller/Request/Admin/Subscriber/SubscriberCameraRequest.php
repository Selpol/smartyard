<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Subscriber;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $house_subscriber_id Идентификатор абонента
 * @property-read int $camera_id Идентификатор камеры
 */
readonly class SubscriberCameraRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'house_subscriber_id' => rule()->id(),
            'camera_id' => rule()->id(),
        ];
    }
}