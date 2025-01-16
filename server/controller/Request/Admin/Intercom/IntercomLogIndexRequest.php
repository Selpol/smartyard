<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 * 
 * @property-read int|null $min_date Минимальная дата
 * @property-read int|null $max_date Максимальная дата
 * 
 * @property-read string|null $message Сообщение лога
 * 
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class IntercomLogIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'min_date' => rule()->int(),
            'max_date' => rule()->int(),

            'message' => rule()->string(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}