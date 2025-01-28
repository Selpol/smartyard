<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Subscriber;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор абонента
 * @property-read int $flat_id Идентификатор квартиры
 */
readonly class SubscriberFlatRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'flat_id' => rule()->id(),
        ];
    }
}