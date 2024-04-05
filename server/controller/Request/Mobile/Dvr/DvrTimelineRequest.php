<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read string|null $token
 */
readonly class DvrTimelineRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'token' => rule()->string()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'token' => 'Токен'
        ];
    }
}