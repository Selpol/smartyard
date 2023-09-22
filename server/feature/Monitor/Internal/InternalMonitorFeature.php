<?php

namespace Selpol\Feature\Monitor\Internal;

use Selpol\Cache\RedisCache;
use Selpol\Feature\Monitor\MonitorFeature;
use Throwable;

class InternalMonitorFeature extends MonitorFeature
{
    public function ping(int $id): bool
    {
        try {
            return container(RedisCache::class)->cache('monitor:' . $id . ':ping', static fn() => intercom($id)?->ping() ?: false, 60);
        } catch (Throwable) {
            return false;
        }
    }

    public function sip(int $id): bool
    {
        try {
            return container(RedisCache::class)->cache('monitor:' . $id . ':sip', static fn() => intercom($id)?->getSipStatus() ?: false, 60);
        } catch (Throwable) {
            return false;
        }
    }
}