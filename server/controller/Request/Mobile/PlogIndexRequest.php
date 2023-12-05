<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $flatId
 *
 * @property-read string $day
 */
readonly class PlogIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'flatId' => rule()->id(),

            'day' => rule()->required()->nonNullable()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'flatId' => 'Идентификатор',

            'day' => 'Дата'
        ];
    }
}