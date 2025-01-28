<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Subscriber;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id Номер телефона
 * 
 * @property-read string $subscriber_name Имя абонента
 * @property-read string $subscriber_patronymic Отчество клиента
 */
readonly class SubscriberStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->string()->regexp('/^(\+\d{1,3}[- ]?)?\d{11}$/')->exist(),

            'subscriber_name' => [filter()->fullSpecialChars(), rule()->string()->max(32)->exist()],
            'subscriber_patronymic' => [filter()->fullSpecialChars(), rule()->string()->max(32)->exist()],
        ];
    }
}
