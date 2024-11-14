<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Block;

use Selpol\Controller\Admin\Block\BlockController;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $flat_id
 *
 * @property-read null|bool $notify
 *
 * @property-read int $service
 *
 * @property-read null|string $cause
 * @property-read null|string $comment
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