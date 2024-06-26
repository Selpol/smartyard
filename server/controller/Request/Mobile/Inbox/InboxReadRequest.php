<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Inbox;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int|null $messageId
 */
readonly class InboxReadRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'messageId' => rule()->int()->clamp(0)
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'messageId' => 'Идентификатор'
        ];
    }
}