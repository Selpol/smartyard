<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $ip Идентификатор проведения
 *
 * @property-read string $title Заголовок
 * @property-read string $name Имя
 * 
 * @property-read null|string $model Модель домофона
 * @property-read null|string $password Пароль домофона
 * 
 * @property-read string $server Сервер
 * 
 * @property-read null|int $dvr_server_id Идентификатор DVR сервера
 * @property-read null|int $frs_server_id Идентификатор FRS сервера
 * 
 * @property-read null|int $address_house_id Идентификатор дома
 * 
 * @property-read double|null $lat Lat камеры
 * @property-read double|null $lon Lon камеры
 */
readonly class IntercomApprovedStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ip' => rule()->ipV4()->exist(),

            'title' => rule()->string()->exist(),
            'name' => rule()->string()->exist(),

            'model' => rule()->string()->in(array_keys(IntercomModel::models())),
            'password' => rule()->string(),

            'server' => rule()->string()->exist(),

            'dvr_server_id' => rule()->int()->clamp(0),
            'frs_server_id' => rule()->int()->clamp(0),

            'address_house_id' => rule()->int()->clamp(0),

            'lat' => rule()->float(),
            'lon' => rule()->float(),
        ];
    }
}