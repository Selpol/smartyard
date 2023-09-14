<?php

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\DeviceException;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Http\Stream;
use Selpol\Service\HttpService;
use Throwable;

abstract class IsIntercom extends IntercomDevice
{
    public string $login = 'root';

    protected ?array $cmses = null;
    protected ?array $rfids = null;
    protected ?array $apartments = null;

    public function getSysInfo(): array
    {
        try {
            $info = $this->get('/system/info');
            $version = $this->get('/v2/system/versions');

            return [
                'DeviceID' => $info['chipId'],
                'DeviceModel' => $info['model'],

                'HardwareVersion' => $version['opt']['versions']['hw']['name'],
                'SoftwareVersion' => $version['opt']['name']
            ];
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function addCmsDefer(int $index, int $dozen, int $unit, int $apartment): void
    {
        if ($this->cmses === null)
            $this->cmses = [];

        if (!array_key_exists($index, $this->cmses))
            $this->cmses[$index] = $this->get('/switch/matrix/' . $index);

        $this->cmses[$index]['matrix'][$dozen][$unit] = $apartment;
    }

    public function addCode(int $code, int $apartment): void
    {
        $this->post('/openCode', ['code' => $code, 'panelCode' => $apartment]);
    }

    public function removeCode(int $apartment): void
    {
        $this->delete('/openCode/' . $apartment);
    }

    public function addRfid(string $code, int $apartment): void
    {
        $this->post('/key/store', ['uuid' => $code, 'panelCode' => $apartment]);
    }

    public function addRfidDeffer(string $code, int $apartment): void
    {
        if ($this->rfids === null)
            $this->rfids = [];

        $this->rfids[] = ['uuid' => $code, 'panelCode' => $apartment];
    }

    public function removeRfid(string $code): void
    {
        $this->delete('/key/store/' . $code);
    }

    public function addApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        $payload = [
            'panelCode' => $apartment,
            'callsEnabled' => ['handset' => $handset, 'sip' => count($sipNumbers) > 0],
            'soundOpenTh' => null,
            'typeSound' => 3,
        ];

        if (count($levels) === 2) $payload['resistances'] = ['answer' => $levels[0], 'quiescent' => $levels[1]];
        else $payload['resistances'] = ['answer' => 255, 'quiescent' => 255];

        $this->post('/panelCode', $payload);

        $this->removeCode($apartment);

        if ($code)
            $this->addCode($code, $apartment);
    }

    public function addApartmentDeffer(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        if ($this->apartments === null) {
            $rawApartments = $this->get('/panelCode');

            $this->apartments = [];

            foreach ($rawApartments as $rawApartment)
                $this->apartments[$rawApartment['panelCode']] = true;
        }

        $this->apartments[$apartment] = ['method' => array_key_exists($apartment, $this->apartments) ? 'PUT' : 'POST', 'handset' => $handset, 'sipNumbers' => $sipNumbers, 'levels' => $levels, 'code' => $code];
    }

    public function removeApartment(int $apartment): void
    {
        $this->delete('/panelCode/' . $apartment);

        $this->removeCode($apartment);
    }

    public function setApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): static
    {
        $payload = [
            'panelCode' => $apartment,
            'callsEnabled' => ['handset' => $handset, 'sip' => count($sipNumbers) > 0],
            'soundOpenTh' => null,
            'typeSound' => 3,
        ];

        if (count($levels) === 2) $payload['resistances'] = ['answer' => $levels[0], 'quiescent' => $levels[1]];
        else $payload['resistances'] = ['answer' => 255, 'quiescent' => 255];

        $this->put('/panelCode/' . $apartment, $payload);

        $this->removeCode($apartment);

        if ($code && strlen((string)$code) === 5)
            $this->addCode($code, $apartment);

        return $this;
    }

    public function setApartmentLevels(int $apartment, int $answer, int $quiescent): static
    {
        $this->put('/panelCode/' . $apartment, ['resistances' => ['answer' => $answer, 'quiescent' => $quiescent]]);

        return $this;
    }

    public function setGate(bool $value): static
    {
        $this->put('/gate/settings', ['gateMode' => $value, 'prefixHouse' => $value]);

        return $this;
    }

    public function setMotionDetection(int $sensitivity, int $left, int $top, int $width, int $height): static
    {
        $this->put('/camera/md', [
            'md_enable' => true,
            'md_frame_shift' => 1,
            'md_area_thr' => 100000,
            'md_rect_color' => '0xFF0000',
            'md_frame_int' => 30,
            'md_rects_enable' => false,
            'md_logs_enable' => true,
            'md_send_snapshot_enable' => true,
            'md_send_snapshot_interval' => 1,

            'snap_send_url' => '',
        ]);

        return $this;
    }

    public function setNtp(string $server, int $port, string $timezone = 'Europe/Moscow'): static
    {
        $this->put('/system/settings', ['tz' => $timezone, 'ntp' => [$server]]);

        return $this;
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        $this->put('/sip/settings', [
            'videoEnable' => true,
            'remote' => [
                'username' => $login,
                'password' => $password,
                'domain' => $server,
                'port' => $port,
            ],
        ]);

        return $this;
    }

    public function setStun(string $server, int $port): static
    {
        return $this;
    }

    public function setSyslog(string $server, int $port): static
    {
        try {
            $this->client()->request(
                container(HttpService::class)
                    ->createRequest('PUT', $this->uri . '/system/files/rsyslogd.conf')
                    ->withBody(Stream::memory($this->getSyslogConfig($server, $port)))
                    ->withHeader('Content-Type', 'text/plain')
            );

            return $this;
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function setMifare(string $key, int $sector): static
    {
        $this->put('/key/settings', [
            'encryption' => [
                'enabled' => true,
                'key_type' => 'A',
                'key_auth' => $key,
                'sector' => $sector,
                'increment' => ['enabled' => false, 'block' => 0, 'openByError' => false]
            ]
        ]);

        return $this;
    }

    public function setAudioLevels(array $levels): static
    {
        if (count($levels) === 6)
            $this->put('/levels', [
                'volumes' => [
                    'panelCall' => $levels[0],
                    'panelTalk' => $levels[1],
                    'thTalk' => $levels[2],
                    'thCall' => $levels[3],
                    'uartFrom' => $levels[4],
                    'uartTo' => $levels[5],
                ],
            ]);

        return $this;
    }

    public function setCallTimeout(int $value): static
    {
        $this->put('/sip/options', ['ringDuration' => $value]);

        return $this;
    }

    public function setTalkTimeout(int $value): static
    {
        $this->put('/sip/options', ['talkDuration' => $value]);

        return $this;
    }

    public function setCmsLevels(array $levels): static
    {
        if (count($levels) === 4)
            $this->put('/levels', [
                'resistances' => [
                    'error' => $levels[0],
                    'break' => $levels[1],
                    'quiescent' => $levels[2],
                    'answer' => $levels[3],
                ],
            ]);

        return $this;
    }

    public function setCmsModel(string $value): static
    {
        $model_id_map = [
            'BK-100M' => 'VISIT',
            'KMG-100' => 'CYFRAL',
            'KKM-100S2' => 'CYFRAL',
            'KM100-7.1' => 'ELTIS',
            'KM100-7.5' => 'ELTIS',
            'COM-100U' => 'METAKOM',
            'COM-220U' => 'METAKOM',
            'FACTORIAL 8x8' => 'FACTORIAL',
        ];

        $id = $model_id_map[strtoupper($value)];

        $this->put('/switch/settings', ['modelId' => $id]);

        $this->clearCms($value);

        return $this;
    }

    public function setConcierge(int $value): static
    {
        $this->put('/panelCode/settings', ['consiergeRoom' => (string)$value]);

        $this->addApartment($value, false, [$value], [], 0);
        $this->removeCode($value);

        return $this;
    }

    public function setSos(int $value): static
    {
        $this->put('/panelCode/settings', ['sosRoom' => (string)$value]);

        return $this;
    }

    public function setPublicCode(int $code): static
    {
        if ($code) $this->addCode($code, 0);
        else $this->removeCode(0);

        return $this;
    }

    public function setDtmf(string $code1, string $code2, string $code3, string $codeOut): static
    {
        $this->put('/sip/options', ['dtmf' => ['1' => $code1, '2' => $code2, '3' => $code3]]);

        return $this;
    }

    public function setUnlockTime(int $time): static
    {
        $relays = $this->get('/relay/info');

        foreach ($relays as $relay)
            $this->put('/relay/' . $relay . '/settings', ['switchTime' => $time]);

        return $this;
    }

    public function setVideoOverlay(string $title): static
    {
        $this->put('/v2/camera/osd', [
            [
                'size' => 1,
                'text' => '',
                'color' => '0xFFFFFF',
                'date' => [
                    'enable' => true,
                    'format' => '%d-%m-%Y',
                ],
                'time' => [
                    'enable' => true,
                    'format' => '%H:%M:%S',
                ],
                'position' => [
                    'x' => 10,
                    'y' => 10,
                ],
                'background' => [
                    'enable' => true,
                    'color' => '0x000000',
                ],
            ],
            [
                'size' => 1,
                'text' => $title,
                'color' => '0xFFFFFF',
                'date' => [
                    'enable' => false,
                    'format' => '%d-%m-%Y',
                ],
                'time' => [
                    'enable' => false,
                    'format' => '%H:%M:%S',
                ],
                'position' => [
                    'x' => 10,
                    'y' => 693,
                ],
                'background' => [
                    'enable' => true,
                    'color' => '0x000000',
                ],
            ],
            [
                'size' => 1,
                'text' => '',
                'color' => '0xFFFFFF',
                'date' => [
                    'enable' => false,
                    'format' => '%d-%m-%Y',
                ],
                'time' => [
                    'enable' => false,
                    'format' => '%H:%M:%S',
                ],
                'position' => [
                    'x' => 10,
                    'y' => 693,
                ],
                'background' => [
                    'enable' => false,
                    'color' => '0x000000',
                ],
            ],
        ]);

        return $this;
    }

    public function unlocked(bool $value): void
    {
        $relays = $this->get('/relay/info');

        foreach ($relays as $relay)
            $this->put('/relay/' . $relay . '/settings', ['alwaysOpen' => $value]);
    }

    public function open(int $value): void
    {
        $this->put('/relay/' . ($value + 1) . '/open');
    }

    public function clearApartment(): void
    {
        $this->delete('/panelCode/clear');
        $this->delete('/openCode/clear');
    }

    public function clearCms(string $model): void
    {
        for ($i = 1; $i <= 3; $i++) {
            if ($model == 'FACTORIAL 8x8') {
                $capacity = 64;

                $matrix = array_fill(0, 8, array_fill(0, 8, null));
            } elseif ($model == 'COM-220U') {
                $capacity = 220;

                $matrix = array_fill(0, 10, array_fill(0, 22, null));
            } else {
                $capacity = 100;

                $matrix = array_fill(0, 10, array_fill(0, 10, null));
            }

            $this->put('/switch/matrix/' . $i, ['capacity' => $capacity, 'matrix' => $matrix]);
        }
    }

    public function clearRfid(): void
    {
        $this->delete('/key/store/clear');
    }

    public function clearCode(): void
    {
        $this->delete('/key/store/clear');
    }

    public function defferCmses(): void
    {
        if ($this->cmses) {
            foreach ($this->cmses as $index => $value)
                $this->put('/switch/matrix/' . $index, ['capacity' => $value['capacity'], 'matrix' => $value['matrix']]);

            $this->cmses = null;
        }
    }

    public function defferRfids(): void
    {
        if ($this->rfids) {
            $this->put('/key/store/merge', $this->rfids);

            $this->rfids = null;
        }
    }

    public function defferApartments(): void
    {
        if ($this->apartments) {
            foreach ($this->apartments as $apartment => $value) {
                if (!is_array($value))
                    continue;

                if ($value['method'] === 'PUT') {
                    $this->setApartment($apartment, $value['handset'], $value['sipNumbers'], $value['levels'], $value['code']);
                } else if ($value['method'] === 'POST') {
                    $this->addApartment($apartment, $value['handset'], $value['sipNumbers'], $value['levels'], $value['code']);
                }
            }

            $this->apartments = null;
        }
    }

    public function deffer(): void
    {
        $this->defferCmses();
        $this->defferRfids();
        $this->defferApartments();
    }

    private function getSyslogConfig(string $server, int $port): string
    {
        return '### TEMPLATES ###
template(name="LongTagForwardFormat" type="list") {
    constant(value="<")
    property(name="pri")
    constant(value=">")
    property(name="timestamp" dateFormat="rfc3339")
    constant(value=" ")
    property(name="hostname")
    constant(value=" ")
    property(name="syslogtag" position.from="1" position.to="32")
    property(name="msg" spifno1stsp="on" )
    property(name="msg")
    constant(value="\n")
}

template (name="ProxyForwardFormat" type="string"
    string="<%PRI%>1 %TIMESTAMP:::date-rfc3339% %FROMHOST-IP% %APP-NAME% %HOSTNAME% - -%msg%")

### RULES ###
*.*;cron.none     @' . $server . ':' . $port . ';ProxyForwardFormat';
    }
}