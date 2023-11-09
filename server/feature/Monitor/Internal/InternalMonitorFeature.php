<?php declare(strict_types=1);

namespace Selpol\Feature\Monitor\Internal;

use Selpol\Feature\Monitor\MonitorFeature;
use Throwable;

readonly class InternalMonitorFeature extends MonitorFeature
{
    public function ping(int $id): bool
    {
        try {
            return intercom($id)
                ?->withTimeout(5)
                ?->withConnectionTimeout(1)
                ?->ping() ?: false;
        } catch (Throwable) {
            return false;
        }
    }

    public function sip(int $id): bool
    {
        try {
            return intercom($id)
                ?->withTimeout(5)
                ?->withConnectionTimeout(1)
                ?->getSipStatus() ?: false;
        } catch (Throwable) {
            return false;
        }
    }
}