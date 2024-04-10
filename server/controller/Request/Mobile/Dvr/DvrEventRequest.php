<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read int $date
 */
readonly class DvrEventRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'date' => rule()->required()->int()->clamp(0, 60),
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'date' => 'Глубина событий'
        ];
    }
}