<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Block;

use Selpol\Controller\Admin\Block\BlockController;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $flat_id Идентификатор квартиры
 *
 * @property-read null|bool $notify Уведомить абонентов
 *
 * @property-read int $service Служба для блокировки
 *
 * @property-read null|string $cause Официальная причина
 * @property-read null|string $comment Комментарий
 */
readonly class BlockFlatStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'flat_id' => rule()->id(),

            'notify' => rule()->bool(),

            'service' => rule()->required()->in(BlockController::SERVICES_FLAT)->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string()
        ];
    }
}