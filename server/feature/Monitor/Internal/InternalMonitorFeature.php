<?php declare(strict_types=1);

namespace Selpol\Feature\Monitor\Internal;

use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Feature\Monitor\MonitorFeature;
use Throwable;

readonly class InternalMonitorFeature extends MonitorFeature
{
    public function sip(int $id): bool
    {
        try {
            $intercom = intercom($id);

            if ($intercom instanceof SipInterface) {
                return $intercom->getSipStatus();
            }

            return false;
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            return false;
        }
    }
}