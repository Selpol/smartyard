<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Block;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор блокировки
 *
 * @property-read null|bool $notify Уведомить абонентов
 *
 * @property-read null|string $cause Официальная причина
 * @property-read null|string $comment Комментарий
 */
readonly class BlockUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'notify' => rule()->bool(),

            'cause' => rule()->string(),
            'comment' => rule()->string()
        ];
    }
}