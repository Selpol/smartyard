<?php declare(strict_types=1);

namespace Selpol\Feature\Monitor\Internal;

use Selpol\Cache\RedisCache;
use Selpol\Feature\Monitor\MonitorFeature;
use Throwable;

readonly class InternalMonitorFeature extends MonitorFeature
{
    public function ping(int $id): bool
    {
        try {
            return intercom($id)?->ping() ?: false;
        } catch (Throwable) {
            return false;
        }
    }

    public function sip(int $id): bool
    {
        try {
            return intercom($id)?->getSipStatus() ?: false;
        } catch (Throwable) {
            return false;
        }
    }
}