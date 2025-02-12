<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Server;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $var_id Идентификатор переменной
 * 
 * @property-read string $var_value Значение переменной
 */
readonly class ServerVariableUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'var_id' => rule()->id(),

            'var_value' => rule()->string()->exist()
        ];
    }
}
