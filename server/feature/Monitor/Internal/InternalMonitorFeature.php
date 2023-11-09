<?php declare(strict_types=1);

namespace Selpol\Feature\Monitor\Internal;

use Selpol\Feature\Monitor\MonitorFeature;
use Throwable;

readonly class InternalMonitorFeature extends MonitorFeature
{
    public function status(int $id): array
    {
        try {
            $intercom = intercom($id);

            if (!$intercom)
                return [];

            $intercom->withTimeout(1500)->withConnectionTimeout(1000);

            if (!$intercom->pingRaw())
                return ['ping' => false];

            return ['ping' => true, 'sip' => $intercom->getSipStatus()];
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);
        }

        return [];
    }

    public function ping(int $id): bool
    {
        try {
            return intercom($id)?->pingRaw() ?: false;
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            return false;
        }
    }

    public function sip(int $id): bool
    {
        try {
            return intercom($id)
                ?->withTimeout(30)
                ?->withConnectionTimeout(15)
                ?->getSipStatus() ?: false;
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            return false;
        }
    }
}