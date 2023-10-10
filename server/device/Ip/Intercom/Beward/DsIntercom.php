<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Trait\BewardTrait;

class DsIntercom extends IntercomDevice
{
    use BewardTrait;

    public string $login = 'admin';

    public function setApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): static
    {
        $params = ['action' => 'set'];

        for ($i = 1; $i <= 5; $i++) {
            if (array_key_exists($i - 1, $sipNumbers)) {
                $params["Acc1ContactEnable$i"] = 'on';
                $params["Acc1ContactNumber$i"] = $sipNumbers[$i - 1];
            } else {
                $params["Acc1ContactEnable$i"] = 'off';
                $params["Acc1ContactNumber$i"] = '';
            }
        }

        $this->get('cgi-bin/sip_cgi', $params);

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

        $this->get('webs/motionCfgEx', $params);

        return $this;
    }

    public function setNtp(string $server, int $port, string $timezone = 'Europe/Moscow'): static
    {
        $tz = match ($timezone) {
            'GMT+03:00' => 21,
            default => 14,
        };

        $this->get('/cgi-bin/ntp_cgi', [
            'action' => 'set',
            'Enable' => 'on',
            'ServerAddress' => $server,
            'ServerPort' => $port,
            'Timezone' => $tz,
            'AutoMode' => 'off',
        ]);

        return $this;
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        $params = [
            'cksip1' => 1,
            'sipname1' => $login,
            'number1' => $login,
            'username1' => $login,
            'pass1' => $password,
            'sipport1' => $port,
            'ckenablesip1' => 1,
            'regserver1' => $server,
            'regport1' => $port,
            'proxyurl1' => '',
            'proxyport1' => 5060,
            'sipserver1' => $server,
            'sipserverport1' => $port,
            'dtfmmod1' => '0',
            'streamtype1' => '0',
            'ckdoubleaudio' => 1,
            'calltime' => 60,
            'ckincall' => '0',
            'ckusemelody' => 1,
            'melodycount' => '0',
            'ckabortontalk' => 1,
            'ckincalltime' => 1,
            'ckintalktime' => 1,
            'regstatus1' => 1,
            'regstatus2' => '0',
            'selcaller' => '0',
        ];

        $this->get('/webs/SIPCfgEx', $params);

        return $this;
    }

    public function setStun(string $server, int $port): static
    {
        $this->get('/webs/SIPCfgEx', ['cknat' => 1, 'stunip' => $server, 'port' => $port]);

        return $this;
    }

    public function setSyslog(string $server, int $port): static
    {
        $this->get('/cgi-bin/rsyslog_cgi', [
            'action' => 'set',
            'Enable' => 'on',
            'Protocol' => 'udp',
            'ServerAddress' => $server,
            'ServerPort' => $port,
            'LogLevel' => 6,
        ]);

        return $this;
    }

    public function setAudioLevels(array $levels): static
    {
        $this->get('/cgi-bin/audio_cgi', [
            'action' => 'set',
            'AudioInVol' => @$levels[0],
            'AudioOutVol' => @$levels[1],
            'AudioInVolTalk' => @$levels[2],
            'AudioOutVolTalk' => @$levels[3],
        ]);

        return $this;
    }

    public function setDtmf(string $code1, string $code2, string $code3, string $codeOut): static
    {
        $this->get('/cgi-bin/sip_cgi', [
            'action' => 'set',
            'DtmfSignal1' => $code1,
            'DtmfBreakCall1' => 'off',
            'DtmfSignal2' => $code2,
            'DtmfBreakCall2' => 'off',
            'DtmfSignal3' => $code3,
            'DtmfBreakCall3' => 'off',
            'DtmfSignalAll' => '',
            'DtmfBreakCallAll' => 'off',
        ]);

        return $this;
    }

    public function setUnlockTime(int $time): static
    {
        $this->get('/webs/almControllerCfgEx', ['outdelay1' => $time]);

        return $this;
    }

    public function setVideoOverlay(string $title): static
    {
        $this->get('/cgi-bin/textoverlay_cgi', [
            'action' => 'set',
            'Title' => $title,
            'TitleValue' => $title ? 1 : 0,
            'DateValue' => 1,
            'TimeValue' => 1,
            'TimeFormat12' => 'False',
            'DateFormat' => 2,
            'WeekValue' => 1,
            'BitrateValue' => 0,
            'Color' => 0,
            'ClientNum' => 0,
        ]);

        return $this;
    }

    public function open(int $value): void
    {
        $this->get('/cgi-bin/alarmout_cgi', ['action' => 'set', 'Output' => $value, 'Status' => 1]);
    }
}