<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 */
readonly class DvrAcquireRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор'
        ];
    }
}