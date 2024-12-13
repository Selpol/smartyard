<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Block;

use Selpol\Controller\Admin\Block\BlockController;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $subscriber_id Идентификатор абонента
 *
 * @property-read null|bool $notify Уведомить абонентов
 *
 * @property-read int $service Служба для блокировки
 *
 * @property-read null|string $cause Официальная причина
 * @property-read null|string $comment Комментарий
 */
readonly class BlockSubscriberStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'subscriber_id' => rule()->id(),

            'notify' => rule()->bool(),

            'service' => rule()->required()->in(BlockController::SERVICES_SUBSCRIBER)->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string()
        ];
    }
}