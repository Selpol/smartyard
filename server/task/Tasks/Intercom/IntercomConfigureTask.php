<?php

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Feature\Address\AddressFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Http\Uri;
use Selpol\Service\DeviceService;
use Selpol\Task\TaskUnique;
use Selpol\Task\TaskUniqueInterface;
use Throwable;

class IntercomConfigureTask extends IntercomTask implements TaskUniqueInterface
{
    public int $id;

    public function __construct(int $id)
    {
        parent::__construct($id, 'Настройка домофона (' . $id . ')');
    }

    public function unique(): TaskUnique
    {
        return new TaskUnique([IntercomConfigureTask::class, $this->id], 3600);
    }

    public function onTask(): bool
    {
        $households = container(HouseFeature::class);

        $domophone = $households->getDomophone($this->id);

        if (!$domophone) {
            file_logger('intercom')->debug('Domophone not found', ['id' => $this->id]);
            return false;
        }

        $this->setProgress(1);

        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $this->id, 'output' => '0']);

        if (!$entrances) {
            file_logger('intercom')->debug('This domophone is not linked with any entrance', ['id' => $this->id]);

            return false;
        }

        $this->setProgress(2);

        $asterisk_server = container(SipFeature::class)->server('ip', $domophone['server'])[0];

        $panel_text = $entrances[0]['callerId'];

        try {
            $device = container(DeviceService::class)->intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

            if (!$device)
                return false;

            if (!$device->ping())
                throw new DeviceException($device, message: 'Устройство не доступно');

            $cms_levels = array_map('intval', explode(',', $entrances[0]['cmsLevels']));
            $cms_model = IntercomCms::model($entrances[0]['cms']);
            $is_shared = $entrances[0]['shared'];

            $this->clean($domophone, $asterisk_server, $cms_levels, $cms_model->model, $device);
            $this->mifare($device);
            $this->cms($is_shared, $entrances, $device);

            $this->setProgress(50);

            $links = [];

            $this->flat($links, $entrances, $cms_levels, $is_shared, $device);

            if ($is_shared)
                $device->setGate($links);

            $this->common($panel_text, $entrances, $device);

            $device->deffer();

            return true;
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable, ['id' => $this->id]);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }

    private function clean(array $domophone, SipServer $asterisk_server, array $cms_levels, ?string $cms_model, IntercomDevice $device): void
    {
        $this->setProgress(5);

        $ntps = config_get('ntp_servers');

        $ntp = new Uri($ntps[array_rand($ntps)]);

        $ntp_server = $ntp->getHost();
        $ntp_port = $ntp->getPort() ?? 123;

        $syslogs = config_get('syslog_servers')[$domophone['json']['syslog']];

        $syslog = new Uri($syslogs[array_rand($syslogs)]);

        $syslog_server = $syslog->getHost();
        $syslog_port = $syslog->getPort() ?? 514;

        $sip_username = sprintf("1%05d", $domophone['domophoneId']);
        $sip_server = $asterisk_server->internal_ip;
        $sip_port = 5060;

        $main_door_dtmf = $domophone['dtmf'];

        $device->clean($sip_server, $ntp_server, $syslog_server, $sip_username, $sip_port, $ntp_port, $syslog_port, $main_door_dtmf, $cms_levels, $cms_model);

        $this->setProgress(25);
    }

    private function cms(bool $is_shared, array $entrances, IntercomDevice $device): void
    {
        if (!$is_shared) {
            $cms_allocation = container(HouseFeature::class)->getCms($entrances[0]['entranceId']);

            foreach ($cms_allocation as $item)
                $device->addCmsDeffer($item['cms'] + 1, $item['dozen'], $item['unit'], $item['apartment']);
        }
    }

    private function flat(array &$links, array $entrances, array $cms_levels, bool $is_shared, IntercomDevice $device): void
    {
        $offset = 0;

        $domophoneId = $this->id;

        foreach ($entrances as $entrance) {
            $flats = container(HouseFeature::class)->getFlats('houseId', $entrance['houseId']);

            if (!$flats) {
                continue;
            }

            $begin = reset($flats)['flat'];
            $end = end($flats)['flat'];

            $links[] = [
                'addr' => container(AddressFeature::class)->getHouse($entrance['houseId'])['houseFull'],
                'prefix' => $entrance['prefix'],
                'begin' => $begin,
                'end' => $end,
            ];

            foreach ($flats as $flat) {
                $block = $flat['autoBlock'] || $flat['adminBlock'] || $flat['manualBlock'];

                $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                    return $entrance['domophoneId'] == $domophoneId;
                });

                if ($flat_entrances) {
                    $apartment = $flat['flat'];
                    $apartment_levels = $cms_levels;

                    foreach ($flat_entrances as $flat_entrance) {
                        if (isset($flat_entrance['apartmentLevels'])) {
                            $apartment_levels = array_map('intval', explode(',', $flat_entrance['apartmentLevels']));
                        }

                        if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment) {
                            $apartment = $flat_entrance['apartment'];
                        }
                    }

                    $device->addApartmentDeffer(
                        $apartment + $offset,
                        $is_shared ? false : ($block ? false : $flat['cmsEnabled']),
                        $is_shared ? [] : [sprintf('1%09d', $flat['flatId'])],
                        $apartment_levels,
                        intval($flat['openCode']) ?? 0
                    );

                    $keys = container(HouseFeature::class)->getKeys('flatId', $flat['flatId']);

                    foreach ($keys as $key)
                        $device->addRfidDeffer($key['rfId'], $apartment);
                }

                if ($flat['flat'] == $end)
                    $offset += $flat['flat'];
            }
        }
    }

    private function common(string $panel_text, array $entrances, IntercomDevice $device): void
    {
        $device->setMotionDetection(0, 0, 0, 0, 0);
        $device->setVideoOverlay($panel_text);
        $device->unlock($entrances[0]['locksDisabled']);
    }

    private function mifare(IntercomDevice $panel): void
    {
        $key = env('MIFARE_KEY');
        $sector = env('MIFARE_SECTOR');

        if ($key !== false && $sector !== false && $key !== null && $sector !== null)
            $panel->setMifare($key, $sector);
    }
}