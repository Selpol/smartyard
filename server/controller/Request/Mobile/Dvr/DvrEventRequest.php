<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read int $after
 * @property-read int $before
 *
 * @property-read string|null $token
 */
readonly class DvrEventRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'after' => rule()->required()->int()->clamp(0)->nonNullable(),
            'before' => rule()->required()->int()->clamp(0)->nonNullable(),

            'token' => rule()->string()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'after' => 'Фильтр событий после',
            'before' => 'Фильтр событий до',

            'token' => 'Токен'
        ];
    }
}