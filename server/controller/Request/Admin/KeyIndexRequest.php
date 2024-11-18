<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|string $rfid RFID-Метка
 * @property-read null|string $comments Комментарий
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class KeyIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'rfid' => rule()->string(),
            'comments' => rule()->string(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}