<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Block;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read null|bool $notify
 *
 * @property-read null|string $cause
 * @property-read null|string $comment
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