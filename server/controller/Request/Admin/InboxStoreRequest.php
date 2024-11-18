<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор абонента
 *
 * @property-read string $title Заголовок
 * @property-read string $body Описание
 * @property-read string $action Тип действия
 */
readonly class InboxStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'body' => rule()->required()->string()->nonNullable(),
            'action' => rule()->required()->string()->nonNullable()
        ];
    }
}