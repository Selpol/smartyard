<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Controller\Request\PageRequest;
use Selpol\Device\Ip\Intercom\IntercomModel;

/**
 * @property-read string|null $comment Комментарий
 * 
 * @property-read string|null $model Модель домофона
 * @property-read string|null $ip IP домофона
 * 
 * @property-read string|null $device_id
 * @property-read string|null $device_model
 * @property-read string|null $device_software_version
 * @property-read string|null $device_hardware_version
 */
readonly class IntercomIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'comment' => rule()->string()->clamp(0, 1000),

            'model' => rule()->string()->in(array_keys(IntercomModel::models())),
            'ip' => rule()->string()->clamp(0, 15),

            'device_id' => rule()->string()->clamp(0, 128),
            'device_model' => rule()->string()->clamp(0, 64),
            'device_software_version' => rule()->string()->clamp(0, 64),
            'device_hardware_version' => rule()->string()->clamp(0, 64),
        ];
    }
}