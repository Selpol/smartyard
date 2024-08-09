<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Inbox;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int|null $date
 *
 * @property-read int $page
 * @property-read int $size
 */
readonly class InboxIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'date' => rule()->int(),

            'page' => rule()->required()->int()->clamp(0),
            'size' => rule()->required()->int()->clamp(0, 512),
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'date' => 'Дата',

            'page' => 'Страница',
            'size' => 'Размер страницы',
        ];
    }
}