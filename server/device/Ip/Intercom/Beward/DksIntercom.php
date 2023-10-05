<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Trait\BewardTrait;

class DksIntercom extends IntercomDevice
{
    use BewardTrait;

    public string $login = 'admin';

    public function getSipStatus(): bool
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/sip_cgi', ['action' => 'regstatus'], parse: false));

        return array_key_exists('AccountReg1', $response) && $response['AccountReg1'] || array_key_exists('AccountReg2', $response) && $response['AccountReg2'];
    }

    public function getLineDialStatus(int $apartment): int
    {
        return (int)trim($this->get('/cgi-bin/intercom_cgi', ['action' => 'linelevel', 'Apartment' => $apartment]));
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

    public function addCmsDefer(int $index, int $dozen, int $unit, int $apartment): void
    {
        $this->addCms($index, $dozen, $unit, $apartment);
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
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'add', 'Key' => $code, 'Apartment' => $apartment]);
        else
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'add', 'Key' => $code, 'Apartment' => $apartment]);
    }

    public function addRfidDeffer(string $code, int $apartment): void
    {
        $this->addRfid($code, $apartment);
    }

    public function removeRfid(string $code): void
    {
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'delete', 'Key' => $code]);
        else
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $code]);
    }

    public function addApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        $this->setApartment($apartment, $handset, $sipNumbers, $levels, $code);
    }

    public function addApartmentDeffer(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        $this->addApartment($apartment, $handset, $sipNumbers, $levels, $code);
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

        for ($i = 1; $i <= count($sipNumbers); $i++)
            $params['Phone' . $i] = $sipNumbers[$i - 1];

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
            'GMT+03:00' => 21,
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
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'set', 'Value' => $key, 'Type' => 1, 'Index' => $sector]);

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
        if (array_key_exists($value, $this->model->cmsesMap))
            $this->get('/webs/kmnDUCfgEx', ['kmntype' => $this->model->cmsesMap[$value]]);

        $this->clearCms($value);

        return $this;
    }

    public function setConcierge(int $value): static
    {
        $this->setIntercomHelp('ConciergeApartment', $value);
        $this->addApartment($value, false, [$value], [], 0);

        return $this;
    }

    public function setSos(int $value): static
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
        $this->post('/cgi-bin/textoverlay_cgi', ['action' => 'set', 'Title' => $title, 'TitleValue' => $title ? 1 : 0, 'DateValue' => 1, 'TimeValue' => 1, 'TimeFormat12' => 'False', 'DateFormat' => 2, 'WeekValue' => 1, 'BitrateValue' => 0, 'Color' => 0, 'ClientNum' => 0]);

        return $this;
    }

    public function unlocked(bool $value): void
    {
        $this->get('/webs/btnSettingEx', ['flag' => '4600', 'paramchannel' => '0', 'paramcmd' => '0', 'paramctrl' => (int)$value, 'paramstep' => '0', 'paramreserved' => '0']);

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

    public function clearApartment(): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => 1, 'LastNumber' => 9999]);
    }

    public function clearCms(string $model): void
    {
        $params = [];

        for ($i = 0; $i <= 8; $i++)
            for ($u = 0; $u <= 9; $u++)
                for ($d = 0; $d <= 25; $d++)
                    $params["du{$i}_{$u}_$d"] = 0;

        $this->post('/webs/kmnDUCfgEx', $params);
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
            $this->removeRfid($rfid);
    }

    public function defferCmses(): void
    {
    }

    public function defferRfids(): void
    {
    }

    public function defferApartments(): void
    {
    }

    public function deffer(): void
    {
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
}