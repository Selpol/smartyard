<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор абонента
 *
 * @property-read string|null $message_id Идентификатор сообщения
 *
 * @property-read int|null $from Дата начала
 * @property-read int|null $to Дата окончания
 */
readonly class InboxIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'message_id' => rule()->string(),

            'from' => rule()->int()->clamp(0),
            'to' => rule()->int()->clamp(0)
        ];
    }
}