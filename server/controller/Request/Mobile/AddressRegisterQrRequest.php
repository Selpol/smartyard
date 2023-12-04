<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $QR
 *
 * @property-read string|int $mobile
 *
 * @property-read string|null $name
 * @property-read string|null $patronymic
 */
readonly class AddressRegisterQrRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'QR' => rule()->required()->nonNullable(),

            'mobile' => rule()->clamp(11, 11),

            'name' => [filter()->fullSpecialChars(), rule()->string()->max(64)],
            'patronymic' => [filter()->fullSpecialChars(), rule()->string()->max(64)],
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'QR' => 'QR-Код',

            'mobile' => 'Мобильный телефон',

            'name' => 'Имя',
            'patronymic' => 'Отчество'
        ];
    }
}