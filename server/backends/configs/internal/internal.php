<?php

namespace backends\configs;

use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;

class internal extends configs
{
    /**
     * @inheritDoc
     */
    public function getDomophonesModels()
    {
        return array_map(static fn(IntercomModel $model) => $model->toArray(), IntercomModel::models());
    }

    /**
     * @inheritDoc
     */
    public function getCamerasModels()
    {
        return array_map(static fn(CameraModel $model) => $model->toArray(), CameraModel::models());
    }

    /**
     * @inheritDoc
     */
    public function getCMSes()
    {
        return array_map(static fn(IntercomCms $cms) => $cms->toArray(), IntercomCms::models());
    }
}
