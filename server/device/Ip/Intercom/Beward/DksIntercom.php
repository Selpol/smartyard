<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

use CURLFile;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Trait\BewardTrait;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

class DksIntercom extends IntercomDevice
{
    use BewardTrait;

    public string $login = 'admin';

    protected ?array $cmses = null;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, IntercomModel $model)
    {
        parent::__construct($uri, $password, $model);

        $this->clientOption->digest($this->login, $this->password);
    }

    public function getSipStatus(): bool
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/sip_cgi', ['action' => 'regstatus'], parse: false));

        return array_key_exists('AccountReg1', $response) && $response['AccountReg1'] == true || array_key_exists('AccountReg2', $response) && $response['AccountReg2'] == true;
    }

    public function getLineDialStatus(int $apartment): int
    {
        return (int)$this->get('/cgi-bin/intercom_cgi', ['action' => 'linelevel', 'Apartment' => $apartment]);
    }

    public function getAllLineDialStatus(int $from, int $to): array
    {
        $result = [];

        for ($i = $from; $i <= $to; $i++)
            $result[$i] = ['resist' => $this->getLineDialStatus($i)];

        return $result;
    }

    public function getRfids(): array
    {
        $result = [];

        if ($this->model->mifare)
            $rfids = $this->parseParamValueHelp($this->get('/cgi-bin/mifare_cgi', ['action' => 'list'], parse: false));
        else
            $rfids = $this->parseParamValueHelp($this->get('/cgi-bin/rfid_cgi', ['action' => 'list'], parse: false));

        foreach ($rfids as $key => $value)
            if (str_contains($key, 'KeyValue') || str_contains($key, 'Key'))
                $result[] = $value;

        return $result;
    }

    public function addCms(int $index, int $dozen, int $unit, int $apartment): void
    {
        $this->get('/cgi-bin/intercomdu_cgi', ['action' => 'set', 'Index' => $index, 'Dozens' => $dozen, 'Units' => $unit, 'Apartment' => $apartment]);
    }

    public function addCmsDeffer(int $index, int $dozen, int $unit, int $apartment): void
    {
        if ($this->cmses === null)
            $this->cmses = [];

        $this->cmses[] = ['index' => $index, 'dozen' => $dozen, 'unit' => $unit, 'apartment' => $apartment];
    }

    public function addCode(int $code, int $apartment): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'set', 'Number' => $apartment, 'DoorCodeActive' => 'on', 'DoorCode' => $code]);
    }

    public function removeCode(int $apartment): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'set', 'Number' => $apartment, 'DoorCodeActive' => 'off']);
    }

    public function addRfid(string $code, int $apartment): void
    {
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'add', 'Key' => $code, 'Apartment' => $apartment, 'CipherIndex' => 1]);
        else
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'add', 'Key' => $code, 'Apartment' => $apartment]);
    }

    public function removeRfid(string $code, int $apartment): void
    {
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'delete', 'Key' => $code, 'Apartment' => $apartment]);
        else
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $code, 'Apartment' => $apartment]);
    }

    public function removeApartment(int $apartment): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => $apartment]);
    }

    public function setApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): static
    {
        $params = [
            'action' => 'set',
            'Number' => $apartment,
            'DoorCodeActive' => $code !== 0 ? 'on' : 'off',
            'RegCodeActive' => 'off',
            'BlockCMS' => $handset ? 'off' : 'on',
            'PhonesActive' => count($sipNumbers) ? 'on' : 'off',
        ];

        if (count($levels) == 2) {
            $params['HandsetUpLevel'] = $levels[0];
            $params['DoorOpenLevel'] = $levels[1];
        }

        if (count($sipNumbers)) {
            $sipNumbers = array_merge([$apartment], $sipNumbers);

            for ($i = 1; $i <= count($sipNumbers); $i++)
                $params['Phone' . $i] = $sipNumbers[$i - 1];
        }

        if ($code !== 0 && $code)
            $params['DoorCode'] = $code;

        $this->get('/cgi-bin/apartment_cgi', $params);

        return $this;
    }

    public function setApartmentLevels(int $apartment, int $answer, int $quiescent): static
    {
        $this->setIntercomHelp('HandsetUpLevel', $answer);
        $this->setIntercomHelp('DoorOpenLevel', $quiescent);

        $this->get('/cgi-bin/apartment_cgi', ['action' => 'levels', 'HandsetUpLevel' => $answer, 'DoorOpenLevel' => $quiescent]);

        return $this;
    }

    public function setApartmentCms(int $apartment, bool $handset): static
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'set', 'BlockCMS' => $handset ? 'off' : 'on']);

        return $this;
    }

    public function setGate(array $value): static
    {
        $params = [
            'action' => 'set',
            'Mode' => 1,
            'Enable' => count($value) ? 'on' : 'off',
            'MainDoor' => 'on',
            'AltDoor' => 'on',
            'PowerRely' => 'on',
        ];

        if (count($value)) {
            $params['EntranceCount'] = count($value);

            for ($i = 0; $i < count($value); $i++) {
                $params['Address' . ($i + 1)] = $value[$i]['addr'];
                $params['Prefix' . ($i + 1)] = $value[$i]['prefix'];
                $params['BegNumber' . ($i + 1)] = $value[$i]['begin'];
                $params['EndNumber' . ($i + 1)] = $value[$i]['end'];
            }
        }

        $this->get('/cgi-bin/gate_cgi', $params);

        return $this;
    }

    public function setVideoEncodingDefault(): static
    {
        $this->get('/webs/videoEncodingCfgEx', [
            'vlevel' => '2',
            'encoder' => '0',
            'sys_cif' => '0',
            'advanced' => '1',
            'ratectrl' => '0',
            'quality' => '1',
            'iq' => '1',
            'rc' => '1',
            'bitrate' => '2048',
            'frmrate' => '25',
            'frmintr' => '25',
            'first' => '0',
            'framingpos' => '0',
            'vlevel2' => '0',
            'encoder2' => '0',
            'sys_cif2' => '1',
            'advanced2' => '1',
            'ratectrl2' => '0',
            'quality2' => '1',
            'iq2' => '1',
            'rc2' => '1',
            'bitrate2' => '348',
            'frmrate2' => '25',
            'frmintr2' => '25',
            'first2' => '0',
            'maxfrmintr' => '200',
            'maxfrmrate' => '25',
            'nlevel' => '1',
            'nfluctuate' => '1',
        ]);

        return $this;
    }

    public function setMotionDetection(int $sensitivity, int $left, int $top, int $width, int $height): static
    {
        $params = [
            'sens' => $sensitivity ? ($sensitivity - 1) : 0,
            'ckdetect' => $sensitivity ? '1' : '0',
            'ckevery' => $sensitivity ? '1' : '0',
            'ckevery2' => '0',
            'begh1' => '0',
            'begm1' => '0',
            'endh1' => 23,
            'endm1' => 59,
            'ckhttp' => '0',
            'alarmoutemail' => '0',
            'ckcap' => '0',
            'ckalarmrecdev' => '0',
        ];

        if ($left) $params['nLeft1'] = $left;
        if ($top) $params['nTop1'] = $top;
        if ($width) $params['nWidth1'] = $width;
        if ($height) $params['nHeight1'] = $height;

        $this->get('/webs/motionCfgEx', $params);

        return $this;
    }

    public function setNtp(string $server, int $port, string $timezone = 'Europe/Moscow'): static
    {
        $tz = match ($timezone) {
            'Europe/Moscow' => 21,
            default => 14
        };

        $this->get('/cgi-bin/ntp_cgi', ['action' => 'set', 'Enable' => 'on', 'ServerAddress' => $server, 'ServerPort' => $port, 'Timezone' => $tz, 'AutoMode' => 'off']);

        return $this;
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        $this->get('/webs/SIP1CfgEx', [
            'cksip' => 1,
            'sipname' => $login,
            'number' => $login,
            'username' => $login,
            'pass' => $password,
            'sipport' => $port,
            'ckenablesip' => 1,
            'regserver' => $server,
            'regport' => $port,
            'sipserver' => $server,
            'sipserverport' => $port,
            'streamtype' => 0,
            'packettype' => 1,
            'dtfmmod' => 0,
            'passchanged' => 1,
            'proxyurl' => '',
            'proxyport' => 5060,
            'ckincall' => 1,
        ]);

        return $this;
    }

    public function setStun(string $server, int $port): static
    {
        $this->get('/webs/SIP1CfgEx', ['cknat' => 1, 'stunip' => $server, 'stunport' => $port]);

        return $this;
    }

    public function setSyslog(string $server, int $port): static
    {
        $this->get('/cgi-bin/rsyslog_cgi', ['action' => 'set', 'Enable' => 'on', 'Protocol' => 'udp', 'ServerAddress' => $server, 'ServerPort' => $port, 'LogLevel' => 6]);

        return $this;
    }

    public function setMifare(string $key, int $sector): static
    {
        if ($this->model->mifare) {
            $this->get('/cgi-bin/cipher_cgi', ['action' => 'add', 'Value' => $key, 'Type' => 1, 'Index' => 1]);
            $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'set', 'Sector' => $sector, 'AutoValidation' => 'off']);
        }

        return $this;
    }

    public function setAudioLevels(array $levels): static
    {
        if (count($levels))
            $this->post('/cgi-bin/audio_cgi', [
                'action' => 'set',
                'AudioInVol' => @$levels[0],
                'AudioOutVol' => @$levels[1],
                'SystemVol' => @$levels[2],
                'AHSVol' => @$levels[3],
                'AHSSens' => @$levels[4],
                'GateInVol' => @$levels[5] - 1, // так надо, баг беварда
                'GateOutVol' => @$levels[6] - 1, // так надо, баг беварда
                'GateAHSVol' => @$levels[7],
                'GateAHSSens' => @$levels[8],
                'MicInSensitivity' => @$levels[9],
                'MicOutSensitivity' => @$levels[10],
                'SpeakerInVolume' => @$levels[11],
                'SpeakerOutVolume' => @$levels[12],
                'KmnMicInSensitivity' => @$levels[13],
                'KmnMicOutSensitivity' => @$levels[14],
                'KmnSpeakerInVolume' => @$levels[15],
                'KmnSpeakerOutVolume' => @$levels[16],
            ]);

        return $this;
    }

    public function setCallTimeout(int $value): static
    {
        return $this->setIntercomHelp('CallTimeout', $value);
    }

    public function setTalkTimeout(int $value): static
    {
        return $this->setIntercomHelp('TalkTimeout', $value);
    }

    public function setCmsLevels(array $levels): static
    {
        if (count($levels) == 2) {
            $this->setIntercomHelp('HandsetUpLevel', $levels[0]);
            $this->setIntercomHelp('DoorOpenLevel', $levels[0]);

            $this->get('/cgi-bin/apartment_cgi', ['action' => 'levels', 'HandsetUpLevel' => $levels[0], 'DoorOpenLevel' => $levels[1]]);
        }

        return $this;
    }

    public function setCmsModel(string $value): static
    {
        if (array_key_exists(strtoupper($value), $this->model->cmsesMap))
            $this->get('/webs/kmnDUCfgEx', ['kmntype' => $this->model->cmsesMap[strtoupper($value)]]);

        $this->clearCms($value);

        return $this;
    }

    public function setConcierge(int $value): static
    {
        $this->setIntercomHelp('ConciergeApartment', $value);
        $this->addApartment($value, false, [$value], [], 0);

        return $this;
    }

    public function setSos(string|int $value): static
    {
        return $this->setAlarmHelp('SOSCallNumber', $value);
    }

    public function setPublicCode(int $code): static
    {
        if ($code) {
            $this->setIntercomHelp('DoorCode', $code);
            $this->setIntercomHelp('DoorCodeActive', 'on');
        } else $this->setIntercomHelp('DoorCodeActive', 'off');

        return $this;
    }

    public function setDtmf(string $code1, string $code2, string $code3, string $codeOut): static
    {
        $this->get('/webs/SIPExtCfgEx', ['dtmfout1' => $code1, 'dtmfout2' => $code2, 'dtmfout3' => $code3]);

        return $this;
    }

    public function setEcho(bool $value): static
    {
        $this->get('/cgi-bin/audio_cgi', ['action' => 'set', 'EchoCancellation' => $value ? 'open' : 'close']);

        return $this;
    }

    public function setUnlockTime(int $time): static
    {
        return $this->setIntercomHelp('DoorOpenTime', $time);
    }

    public function setDisplayText(string $title): static
    {
        $this->post('/cgi-bin/display_cgi', ['action' => 'set', 'TickerEnable' => $title ? 'on' : 'off', 'TickerText' => $title, 'TickerTimeout' => 125, 'LineEnable1' => 'off', 'LineEnable2' => 'off', 'LineEnable3' => 'off', 'LineEnable4' => 'off', 'LineEnable5' => 'off']);

        return $this;
    }

    public function setVideoOverlay(string $title): static
    {
        $this->post('/cgi-bin/textoverlay_cgi', ['action' => 'set', 'Title' => $title, 'TitleValue' => $title ? 1 : 0, 'DateValue' => 1, 'TimeValue' => 1, 'TimeFormat12' => 'False', 'DateFormat' => 2, 'WeekValue' => 0, 'BitrateValue' => 0, 'Color' => 0, 'ClientNum' => 0]);

        return $this;
    }

    public function unlock(bool $value): void
    {
        $this->get('/webs/btnSettingEx', ['flag' => 4601, 'paramchannel' => 0, 'paramcmd' => 0, 'paramctrl' => (int)$value, 'paramstep' => 0, 'paramreserved' => 0]);
        $this->setIntercomHelp('DoorOpenMode', $value ? 'on' : 'off');
    }

    public function open(int $value): void
    {
        switch ($value) {
            case 0:
                $this->get('/cgi-bin/intercom_cgi', ['action' => 'maindoor']);

                break;
            case 1:
                $this->get('/cgi-bin/intercom_cgi', ['action' => 'altdoor']);

                break;
            case 2:
                $this->get('/cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'on']);

                sleep(100);

                $this->get('/cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'off']);

                break;
        }
    }

    public function call(int $apartment): void
    {
        $this->get('/cgi-bin/sip_cgi', ['action' => 'call', 'Uri' => $apartment]);
    }

    public function reboot(): void
    {
        $this->get('/webs/btnHitEx', ['flag' => 21]);
    }

    public function reset(): void
    {
        $this->get('/cgi-bin/factorydefault_cgi');
    }

    public function clearApartment(): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => 1, 'LastNumber' => 9999]);
    }

    public function clearCms(string $model): void
    {
    }

    public function clearRfid(): void
    {
        if ($this->model->mifare) {
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'clear']);
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'delete', 'Apartment' => 0]);
        } else {
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'clear']);
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Apartment' => 0]);
        }

        foreach ($this->getRfids() as $rfid)
            $this->removeRfid($rfid, 0);
    }

    public function defferCmses(): void
    {
        if ($this->cmses) {
            ['model' => $model, 'cmses' => $cmses] = $this->cmsExport();

            foreach ($this->cmses as $cms)
                $cmses[$cms['index'] - 1][$cms['unit']][$cms['dozen']] = $cms['apartment'];

            $content = $model . PHP_EOL . PHP_EOL;

            foreach ($cmses as $cms) {
                foreach ($cms as $cm)
                    $content .= implode(',', $cm) . PHP_EOL;

                $content .= PHP_EOL;
            }

            $filename = tempnam(sys_get_temp_dir(), 'dks-matrik');

            $stream = fopen($filename, 'w');
            fwrite($stream, $content);
            fclose($stream);

            try {
                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->uri . '/cgi-bin/intercomdu_cgi?action=import',
                    CURLOPT_POST => 1,
                    CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
                    CURLOPT_USERPWD => $this->login . ':' . $this->password,
                    CURLOPT_HTTPHEADER => ['Content-Type:multipart/form-data'],
                    CURLOPT_POSTFIELDS => ['data-binary' => new CURLFile($filename, posted_filename: 'matrix.csv'), 'text/csv'],
                    CURLOPT_INFILESIZE => strlen($content)
                ]);

                curl_exec($ch);
                curl_close($ch);
            } finally {
                unlink($filename);
            }

            $this->cmses = null;
        }
    }

    public function deffer(): void
    {
        $this->defferCmses();
    }

    protected function setAlarmHelp(string $name, mixed $value): static
    {
        $this->get('/cgi-bin/intercom_alarm_cgi', ['action' => 'set', $name => $value]);

        return $this;
    }

    protected function setIntercomHelp(string $name, mixed $value): static
    {
        $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', $name => $value]);

        return $this;
    }

    private function cmsExport(): array
    {
        $content = $this->get('/cgi-bin/intercomdu_cgi', ['action' => 'export'], parse: false);
        $lines = explode(PHP_EOL, $content);

        $model = 0;
        $cmses = [];

        for ($i = 0; $i < count($lines); $i++) {
            if ($i === 0) $model = intval($lines[$i]);
            else if ($lines[$i] === '') $cmses[] = [];
            else {
                $count = count(explode(',', $lines[$i]));

                if ($count)
                    $cmses[count($cmses) - 1][] = array_fill(0, $count, 0);
            }
        }

        return ['model' => $model, 'cmses' => array_filter($cmses, static fn(array $cms) => count($cms) > 0)];
    }
}