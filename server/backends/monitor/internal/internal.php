<?php

namespace backends\monitoring\internal;

use backends\monitoring\monitor;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\IpDevice;
use Throwable;

class internal extends monitor
{
    public function ping(IpDevice $device): bool
    {
        try {
            return redis_cache('monitor:' . $device->uri->getHost() . ':ping', static fn() => $device->ping(), 30);
        } catch (Throwable) {
            return false;
        }
    }

    public function sip(IntercomDevice $device): bool
    {
        try {
            return redis_cache('monitor:' . $device->uri->getHost() . ':sip', static fn() => $device->getSipStatus(), 30);
        } catch (Throwable) {
            return false;
        }
    }
}