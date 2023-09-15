<?php

namespace backends\configs;

use backends\backend;

abstract class configs extends backend
{
    /**
     * @return mixed
     */
    abstract public function getDomophonesModels();

    /**
     * @return false|array
     */
    abstract public function getCamerasModels();

    /**
     * @return false|array
     */
    abstract public function getCMSes();
}
