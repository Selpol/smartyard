<?php

declare(strict_types=1);

namespace Selpol\Feature\Monitor\Internal;

use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Monitor\MonitorFeature;
use Selpol\Feature\Schedule\ScheduleTimeInterface;
use Throwable;

readonly class InternalMonitorFeature extends MonitorFeature
{
    public function cron(ScheduleTimeInterface $value): bool
    {
        if (!config_get('feature.monitor.enable', false) || !$value->minutely()) {
            return true;
        }

        $usable = $this->getRedis()->monitor();

        /** @var DeviceCamera[] $cameras */
        $cameras = DeviceCamera::fetchAll();

        /** @var array<int, array<string, bool>> $dvrs */
        $dvrs = [];

        foreach ($cameras as $camera) {
            if (!$camera->dvr_server_id || !$camera->dvr_stream) {
                $usable->set('status:' . $camera->camera_id, false);

                continue;
            }

            if (!array_key_exists($camera->dvr_server_id, $dvrs)) {
                $dvr = dvr($camera->dvr_server_id);

                if (!$dvr) {
                    $usable->set('status:' . $camera->camera_id, false);

                    continue;
                }

                $dvrs[$camera->dvr_server_id] = $dvr->getStatuses(null);
            }

            if (!array_key_exists($camera->dvr_stream, $dvrs[$camera->dvr_server_id])) {
                $usable->set('status:' . $camera->camera_id, false);

                continue;
            }

            $usable->set('status:' . $camera->camera_id, $dvrs[$camera->dvr_server_id][$camera->dvr_stream]);
        }

        return true;
    }

    public function status(int $id): bool
    {
        return $this->getRedis()->monitor()->get('status:' . $id) == true;
    }

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
