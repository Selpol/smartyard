<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $login Логин
 * @property-read string $password Пароль
 * 
 * @property-read bool $remember_me Запомнить вход, как уникальный
 * 
 * @property-read string|null $user_agent User-Agent пользователя
 * @property-read string|null $did Уникальный идентификатор
 */
readonly class AuthenticationRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'login' => [filter()->fullSpecialChars(), rule()->string()->min(4)->exist()],
            'password' => [filter()->fullSpecialChars(), rule()->string()->min(8)->exist()],

            'remember_me' => rule()->bool()->exist(),

            'user_agent' => [filter()->fullSpecialChars(), rule()->string()],
            'did' => [filter()->fullSpecialChars(), rule()->string()]
        ];
    }
}
