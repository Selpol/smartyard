<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Inbox;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $date
 */
readonly class InboxShowRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'date' => rule()->required()->string()->nonNullable()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'date' => 'Дата'
        ];
    }
}