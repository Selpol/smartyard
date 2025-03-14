<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\House;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор дома
 *
 * @property-read array $keys Список ключей {rfId, accessTo, comment?}[]
 */
readonly class HouseKeyRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'keys' => rule()->array()->exist(),
            'keys.*.rfId' => rule()->string()->exist(),
            'keys.*.accessTo' => rule()->id(),
            'keys.*.comment' => rule()->string()
        ];
    }
}