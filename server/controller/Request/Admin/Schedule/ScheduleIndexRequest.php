<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Schedule;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read null|string $title Заголовок
 *
 * @property-read null|int $status Статус
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class ScheduleIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'title' => rule()->string()->max(256),

            'status' => rule()->int()->clamp(0, 1),
        ];
    }
}