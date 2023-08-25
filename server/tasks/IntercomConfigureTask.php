<?php

namespace tasks;

use Exception;
use hw\domophones\domophones;
use Throwable;

class IntercomConfigureTask extends Task
{
    public int $id;
    public bool $first;

    public function __construct(int $id, bool $first)
    {
        parent::__construct('Настройка домофона (' . $id . ')');

        $this->id = $id;
        $this->first = $first;
    }

    public function onTask()
    {
        $households = loadBackend('households');
        $addresses = loadBackend('addresses');
        $configs = loadBackend('configs');
        $sip = loadBackend("sip");

        $domophone = $households->getDomophone($this->id);

        if (!$domophone) {
            echo 'Domophone not found' . PHP_EOL;

            return;
        }

        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $this->id, 'output' => '0']);

        if (!$entrances) {
            echo 'This domophone is not linked with any entrance' . PHP_EOL;

            return;
        }

        $asterisk_server = $sip->server("ip", $domophone['server']);
        $cmses = $configs->getCMSes();
        $panel_text = $entrances[0]['callerId'];

        try {
            /** @var domophones $panel */
            $panel = loadDomophone($domophone['model'], $domophone['url'], $domophone['credentials'], $this->first);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";

            return;
        }

        if ($this->first) {
            $panel->prepare();
        }

        $ntps = $this->config['ntp_servers'];
        $ntp = parseURI($ntps[array_rand($ntps)]);
        $ntp_server = $ntp['host'];
        $ntp_port = $ntp['port'] ?? 123;

        $syslogs = $this->config['syslog_servers'][$domophone['json']['syslog']];

        $syslog = parseURI($syslogs[array_rand($syslogs)]);
        $syslog_server = $syslog['host'];
        $syslog_port = $syslog['port'] ?? 514;

        $sip_username = sprintf("1%05d", $domophone['domophoneId']);
        $sip_server = $asterisk_server['ip'];
        $sip_port = @$asterisk_server['sip_udp_port'] ?? 5060;

        $nat = (bool)$domophone['nat'];

        $stun = parseURI($sip->stun(''));
        $stun_server = $stun['host'];
        $stun_port = $stun['port'] ?? 3478;

        $audio_levels = [];
        $main_door_dtmf = $domophone['dtmf'];

        $cms_levels = explode(',', $entrances[0]['cmsLevels']);
        $cms_model = (string)@$cmses[$entrances[0]['cms']]['model'];

        $is_shared = $entrances[0]['shared'];

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

        if (!$is_shared) {
            $cms_allocation = $households->getCms($entrances[0]['entranceId']);

            foreach ($cms_allocation as $item) {
                $panel->configure_cms_raw($item['cms'], $item['dozen'], $item['unit'], $item['apartment'], $cms_model);
            }
        }

        $links = [];
        $offset = 0;

        foreach ($entrances as $entrance) {
            $flats = $households->getFlats('houseId', $entrance['houseId']);

            if (!$flats) {
                continue;
            }

            $begin = reset($flats)['flat'];
            $end = end($flats)['flat'];

            $links[] = [
                'addr' => $addresses->getHouse($entrance['houseId'])['houseFull'],
                'prefix' => $entrance['prefix'],
                'begin' => $begin,
                'end' => $end,
            ];

            foreach ($flats as $flat) {
                $domophoneId = $this->id;

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

                    $keys = $households->getKeys('flatId', $flat['flatId']);

                    foreach ($keys as $key) {
                        $panel->add_rfid($key['rfId'], $apartment);
                    }
                }

                if ($flat['flat'] == $end) {
                    $offset += $flat['flat'];
                }
            }
        }

        if ($is_shared) {
            $panel->configure_gate($links);
        }

        $panel->configure_md();
        $panel->set_display_text($panel_text);
        $panel->set_video_overlay($panel_text);
        $panel->keep_doors_unlocked($entrances[0]['locksDisabled']);

        loadEnvFile();

        $key = getenv('MIFARE_KEY');
        $sector = getenv('MIFARE_SECTOR');

        if ($key !== false && $sector !== false)
            $panel->configure_mifare($key, $sector);
    }

    public function onError(Throwable $throwable)
    {
        task(new IntercomConfigureTask($this->id, $this->first))->low()->delay(600)->dispatch();
    }
}