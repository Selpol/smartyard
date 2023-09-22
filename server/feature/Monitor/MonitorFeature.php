<?php

namespace Selpol\Feature\Monitor;

use Selpol\Feature\Feature;

abstract class MonitorFeature extends Feature
{
    public abstract function ping(int $id): bool;

    public abstract function sip(int $id): bool;
}