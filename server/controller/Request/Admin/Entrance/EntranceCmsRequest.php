<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Entrance;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор входа
 * 
 * @property-read array $cmses Массив КМС входа
 */
readonly class EntranceCmsRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'cmses' => rule()->array()->exist(),
            'cmses.*.cms' => rule()->string()->exist(),
            'cmses.*.dozen' => rule()->int()->clamp(0)->exist(),
            'cmses.*.unit' => rule()->string()->exist(),
            'cmses.*.apartment' => rule()->int()->clamp(0)->exist()
        ];
    }
}