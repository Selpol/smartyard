<?php

namespace Selpol\Task\Tasks\Intercom;

use Exception;
use hw\domophones\domophones;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Http\Uri;
use Selpol\Service\DomophoneService;
use Throwable;

class IntercomConfigureTask extends IntercomTask
{
    public const SYNC_FIRST = 1 << 0;

    public int $id;

    public int $flags;

    public function __construct(int $id, int $flags = 0)
    {
        parent::__construct($id, (($flags & self::SYNC_FIRST) == self::SYNC_FIRST) ? ('Первичная настройка домофона (' . $id . ')') : ('Настройка домофона (' . $id . ')'));

        $this->flags = $flags;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function onTask(): bool
    {
        $households = backend('households');
        $configs = backend('configs');

        $domophone = $households->getDomophone($this->id);

        if (!$domophone) {
            echo 'Domophone not found' . PHP_EOL;

            return false;
        }

        $this->setProgress(1);

        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $this->id, 'output' => '0']);

        if (!$entrances) {
            echo 'This domophone is not linked with any entrance' . PHP_EOL;

            return false;
        }

        $this->setProgress(2);

        $asterisk_server = backend('sip')->server("ip", $domophone['server']);
        $cmses = $configs->getCMSes();
        $panel_text = $entrances[0]['callerId'];

        $first = ($this->flags & self::SYNC_FIRST) == self::SYNC_FIRST;

        try {
            $panel = container(DomophoneService::class)->get($domophone['model'], $domophone['url'], $domophone['credentials'], $first);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";

            return false;
        }

        $cms_levels = explode(',', $entrances[0]['cmsLevels']);
        $cms_model = (string)@$cmses[$entrances[0]['cms']]['model'];
        $is_shared = $entrances[0]['shared'];

        $this->main($first, $domophone, $asterisk_server, $cms_levels, $cms_model, $panel);
        $this->cms($is_shared, $entrances, $cms_model, $panel);

        $this->setProgress(50);

        $links = [];

        $this->flat($links, $entrances, $cms_levels, $is_shared, $panel);

        if ($is_shared)
            $panel->configure_gate($links);

        $this->common($panel_text, $entrances, $panel);
        $this->mifare($panel);

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        task(new IntercomConfigureTask($this->id, $this->flags))->low()->delay(600)->dispatch();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function main(bool $first, array $domophone, array $asterisk_server, array $cms_levels, string $cms_model, domophones $panel): void
    {
        $this->setProgress(5);

        if ($first)
            $panel->prepare();

        $this->setProgress(10);

        $ntps = config('ntp_servers');

        $ntp = new Uri($ntps[array_rand($ntps)]);

        $ntp_server = $ntp->getHost();
        $ntp_port = $ntp->getPort() ?? 123;

        $syslogs = config('syslog_servers')[$domophone['json']['syslog']];

        $syslog = new Uri($syslogs[array_rand($syslogs)]);

        $syslog_server = $syslog->getHost();
        $syslog_port = $syslog->getPort() ?? 514;

        $sip_username = sprintf("1%05d", $domophone['domophoneId']);
        $sip_server = $asterisk_server['ip'];
        $sip_port = @$asterisk_server['sip_udp_port'] ?? 5060;

        $nat = (bool)$domophone['nat'];

        $stun = new Uri(backend('sip')->stun(''));

        $stun_server = $stun->getHost();
        $stun_port = $stun->getPort() ?? 3478;

        $audio_levels = [];
        $main_door_dtmf = $domophone['dtmf'];

        $panel->clean(
            $sip_server,
            $ntp_server,
            $syslog_server,
            $sip_username,
            $sip_port,
            $ntp_port,
            $syslog_port,
            $main_door_dtmf,
            $audio_levels,
            $cms_levels,
            $cms_model,
            $nat,
            $stun_server,
            $stun_port
        );

        $this->setProgress(25);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function cms(bool $is_shared, array $entrances, string $cms_model, domophones $panel): void
    {
        if (!$is_shared) {
            $cms_allocation = backend('households')->getCms($entrances[0]['entranceId']);

            foreach ($cms_allocation as $item)
                $panel->configure_cms_raw($item['cms'], $item['dozen'], $item['unit'], $item['apartment'], $cms_model);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function flat(array &$links, array $entrances, array $cms_levels, bool $is_shared, domophones $panel): void
    {
        $offset = 0;

        $domophoneId = $this->id;

        foreach ($entrances as $entrance) {
            $flats = backend('households')->getFlats('houseId', $entrance['houseId']);

            if (!$flats) {
                continue;
            }

            $begin = reset($flats)['flat'];
            $end = end($flats)['flat'];

            $links[] = [
                'addr' => backend('addresses')->getHouse($entrance['houseId'])['houseFull'],
                'prefix' => $entrance['prefix'],
                'begin' => $begin,
                'end' => $end,
            ];

            foreach ($flats as $flat) {
                $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                    return $entrance['domophoneId'] == $domophoneId;
                });

                if ($flat_entrances) {
                    $apartment = $flat['flat'];
                    $apartment_levels = $cms_levels;

                    foreach ($flat_entrances as $flat_entrance) {
                        if (isset($flat_entrance['apartmentLevels'])) {
                            $apartment_levels = explode(',', $flat_entrance['apartmentLevels']);
                        }

                        if ($flat_entrance['apartment'] != $apartment) {
                            $apartment = $flat_entrance['apartment'];
                        }
                    }

                    $panel->configure_apartment(
                        $apartment + $offset,
                        (bool)$flat['openCode'],
                        $is_shared ? false : $flat['cmsEnabled'],
                        $is_shared ? [] : [sprintf('1%09d', $flat['flatId'])],
                        $flat['openCode'] ?: 0,
                        $apartment_levels
                    );

                    $keys = backend('households')->getKeys('flatId', $flat['flatId']);

                    foreach ($keys as $key)
                        $panel->add_rfid($key['rfId'], $apartment);
                }

                if ($flat['flat'] == $end)
                    $offset += $flat['flat'];
            }
        }
    }

    private function common(string $panel_text, array $entrances, domophones $panel): void
    {

        $this->setProgress(75);

        $panel->configure_md();

        $this->setProgress(80);

        $panel->set_display_text($panel_text);

        $this->setProgress(85);

        $panel->set_video_overlay($panel_text);

        $this->setProgress(90);

        $panel->keep_doors_unlocked($entrances[0]['locksDisabled']);

        $this->setProgress(95);
    }

    private function mifare(domophones $panel): void
    {
        $key = env('MIFARE_KEY');
        $sector = env('MIFARE_SECTOR');

        if ($key !== false && $sector !== false)
            $panel->configure_mifare($key, $sector);
    }
}