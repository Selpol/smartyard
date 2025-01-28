<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Subscriber;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $house_subscriber_id Идентификатор абонента
 * 
 * @property-read string $subscriber_name Имя абонента
 * @property-read string $subscriber_patronymic Отчество клиента
 */
readonly class SubscriberUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'house_subscriber_id' => rule()->id(),

            'subscriber_name' => [filter()->fullSpecialChars(), rule()->string()->max(32)->exist()],
            'subscriber_patronymic' => [filter()->fullSpecialChars(), rule()->string()->max(32)->exist()],
        ];
    }
}
