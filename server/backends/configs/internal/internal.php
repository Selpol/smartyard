<?php

namespace backends\configs;

use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;

class internal extends configs
{
    public function getDomophonesModels(): mixed
    {
        return array_map(static fn(IntercomModel $model) => $model->toArray(), IntercomModel::models());
    }

    public function getCamerasModels(): bool|array
    {
        return array_map(static fn(CameraModel $model) => $model->toArray(), CameraModel::models());
    }

    public function getCMSes(): bool|array
    {
        return array_map(static fn(IntercomCms $cms) => $cms->toArray(), IntercomCms::models());
    }
}
