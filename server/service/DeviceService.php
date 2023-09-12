<?php

namespace Selpol\Service;

use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Http\Uri;

class DeviceService
{
    public function camera(string $model, string $url, string $password): CameraDevice|false
    {
        $models = CameraModel::models();

        if (str_ends_with($model, '.json'))
            $model = substr($model, 0, -5);

        if (array_key_exists($model, $models))
            return new $models[$model]->class(new Uri($url), $password);

        return false;
    }

    public function intercom(string $model, string $url, string $password): IntercomDevice|false
    {
        $models = IntercomModel::models();

        if (str_ends_with($model, '.json'))
            $model = substr($model, 0, -5);

        if (array_key_exists($model, $models))
            return new $models[$model]->class(new Uri($url), $password);

        return false;
    }
}