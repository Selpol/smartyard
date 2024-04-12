<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read int $date
 *
 * @property-read string|null $token
 */
readonly class DvrEventRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'date' => rule()->required()->int()->clamp(0, 60),

            'token' => rule()->string()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'date' => 'Глубина событий',

            'token' => 'Токен'
        ];
    }
}