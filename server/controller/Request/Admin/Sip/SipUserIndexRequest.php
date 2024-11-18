<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Sip;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|int $type Префикс номера
 * @property-read null|string $title Имя аккаунта
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class SipUserIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'type' => rule()->int()->clamp(1, 9),
            'title' => rule()->string(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}