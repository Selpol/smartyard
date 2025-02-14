<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\User;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $login Логин
 * @property-read string $password Пароль
 * 
 * @property-read string $name Имя
 * 
 * @property-read string|null $phone Номер телефона
 * 
 * @property-read int $enabled Включен ли пользователь
 */
readonly class UserStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'login' => [filter()->fullSpecialChars(), rule()->string()->min(4)->exist()],
            'password' => [filter()->fullSpecialChars(), rule()->string()->min(8)->exist()],

            'name' => [filter()->fullSpecialChars(), rule()->string()->exist()],

            'phone' => rule()->string()->regexp('/^(\+\d{1,3}[- ]?)?\d{11}$/'),

            'enabled' => rule()->int()->clamp(0, 1)->exist()
        ];
    }
}
