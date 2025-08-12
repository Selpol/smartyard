<?php

declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор DVR сервера
 * 
 * @property-read array $cameras Идентификаторы камер на DVR сервере
 * 
 * @property-read null|int $frs_server_id Идентификатор FRS сервера
 * @property-read null|int $address_house_id Идентификатор дома
 * 
 * @property-read string $model Модель камеры
 */
readonly class DvrImportRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'cameras' => rule()->array()->exist(),
            'cameras.*' => rule()->string()->exist(),

            'frs_server_id' => rule()->int()->clamp(0),
            'address_house_id' => rule()->int()->clamp(0),

            'model' => [filter()->default('fake'), rule()->string()->in(array_keys(CameraModel::models()))->exist()]
        ];
    }
}
