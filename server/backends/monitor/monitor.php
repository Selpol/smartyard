<?php

namespace backends\monitoring;

use backends\backend;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\IpDevice;

abstract class monitor extends backend
{
    public abstract function ping(IpDevice $device): bool;

    public abstract function sip(IntercomDevice $device): bool;
}