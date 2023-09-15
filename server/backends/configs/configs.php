<?php

namespace backends\configs;

use backends\backend;

abstract class configs extends backend
{
    abstract public function getDomophonesModels(): mixed;

    abstract public function getCamerasModels(): bool|array;

    abstract public function getCMSes(): bool|array;
}
