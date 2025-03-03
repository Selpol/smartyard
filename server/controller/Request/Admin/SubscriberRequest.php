<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|int[] $ids Идентификаторы абонентов
 * 
 * @property-read int|null $flat_id Идентификатор квартиры
 * 
 * @property-read null|string $name Имя
 * @property-read null|string $patronymic Отчество
 *
 * @property-read null|string $mobile Номер телефона
 * 
 * @property-read null|string $platform Платформа. 0 - ANDROID, 1 - IOS, 2 - WEB
 * @property-read null|string $push_token_type Тип токена. 0 - GMS, 1 - IOS PROD, 2 - IOS DEV, 3 - HMS, 4 - RSR
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class SubscriberRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ids' => rule()->array(),
            'ids.*' => rule()->id(),

            'flat_id' => rule()->int()->clamp(0),

            'name' => rule()->string()->clamp(0, 64),
            'patronymic' => rule()->string()->clamp(0, 64),

            'mobile' => rule()->string()->clamp(11, 11)->regexp('/^7\d{10}$/'),

            'platform' => rule()->int()->in([0, 1, 2]),
            'push_token_type' => rule()->int()->in([0, 1, 2, 3, 4]),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}