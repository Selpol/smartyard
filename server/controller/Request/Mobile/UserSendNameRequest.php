<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $name
 * @property-read string|null $patronymic
 */
readonly class UserSendNameRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'name' => [filter()->fullSpecialChars(), rule()->required()->string()->max(64)->nonNullable()],
            'patronymic' => [filter()->fullSpecialChars(), rule()->string()->max(64)]
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'name' => 'Имя',
            'patronymic' => 'Отчество'
        ];
    }
}