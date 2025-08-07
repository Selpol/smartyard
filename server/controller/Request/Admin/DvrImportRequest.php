<?php

declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор DVR сервера
 * 
 * @property-read array $cameras Идентификаторы камер на DVR сервере
 * 
 * @property-read null|int $frs_server_id Идентификатор FRS сервера
 */
readonly class DvrImportRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'cameras' => rule()->array()->exist(),
            'cameras.*' => rule()->string()->exist(),

            'frs_server_id' => rule()->int()->clamp(0)
        ];
    }
}
