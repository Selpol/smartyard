<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Camera;

use Selpol\Controller\Request\PageRequest;
use Selpol\Device\Ip\Camera\CameraModel;

/**
 * @property-read int|null $dvr_server_id Идентификатор сервера архива
 * @property-read int|null $frs_server_id Идентификатор сервера лиц
 *
 * @property-read int $enabled Статус камеры
 *
 * @property-read string $model Модель камеры
 * @property-read string $url URL Камеры
 * @property-read string|null $stream
 * @property-read string $credentials Авторизация камеры
 * @property-read string|null $name Имя камеры
 * @property-read string|null $dvr_stream Идентификатор стрима на сервере ахрива
 * @property-read string|null $timezone Временная зона камеры
 *
 * @property-read double|null $lat Позиция камеры
 * @property-read double|null $lon Позиция камеры
 *
 * @property-read int|null $common
 *
 * @property-read string|null $ip
 *
 * @property-read string|null $comment Комментарий камеры
 * 
 * @property-read string|null $config Конфигурация камеры
 *
 * @property-read bool $hidden Скрытая ли камера
 */
readonly class CameraStoreRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'dvr_server_id' => rule()->int()->clamp(0),
            'frs_server_id' => rule()->int()->clamp(0),

            'enabled' => rule()->required()->int()->nonNullable(),

            'model' => rule()->required()->in(array_keys(CameraModel::models()))->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'stream' => rule()->string(),
            'credentials' => rule()->required()->string()->nonNullable(),
            'name' => rule()->string(),
            'dvr_stream' => rule()->string(),
            'timezone' => rule()->string(),

            'lat' => rule()->float(),
            'lon' => rule()->float(),

            'common' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'config' => rule()->string(),

            'hidden' => rule()->bool()
        ];
    }
}