<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Common\DDns;
use Selpol\Device\Ip\Intercom\Setting\Common\Gate;
use Selpol\Device\Ip\Intercom\Setting\Common\Mifare;
use Selpol\Device\Ip\Intercom\Setting\Common\Ntp;
use Selpol\Device\Ip\Intercom\Setting\Common\Relay;
use Selpol\Device\Ip\Intercom\Setting\Common\Room;
use Selpol\Device\Ip\Intercom\Setting\Common\Stun;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;

trait CommonTrait
{
    public function getNtp(): Ntp
    {
        $response = $this->get('/cgi-bin/ntp_cgi', ['action' => 'get'], parse: ['type' => 'param']);

        return new Ntp($response['ServerAddress'], intval($response['ServerPort']), '21');
    }

    public function getStun(): Stun
    {
        return new Stun('', 0);
    }

    public function getSyslog(): Syslog
    {
        $response = $this->get('/cgi-bin/rsyslog_cgi', ['action' => 'get'], parse: ['type' => 'param']);

        return new Syslog($response['ServerAddress'], intval($response['ServerPort']));
    }

    public function getMifare(): Mifare
    {
        if ($this->mifare) {
            $cipher = $this->get('/cgi-bin/cipher_cgi', ['action' => 'list'], parse: ['type' => 'param']);
            $mifare = $this->get('/cgi-bin/' . $this->resolver->string('mifare.cgi', 'mifareusr_cgi'), ['action' => 'get'], parse: ['type' => 'param']);

            if (!$cipher || !$mifare) {
                return new Mifare(false, '', 0);
            }

            return new Mifare(true, array_key_exists('Value1', $cipher) ? $cipher['Value1'] : '', intval($mifare['Sector']));
        }

        return new Mifare(false, '', 0);
    }

    public function getRoom(): Room
    {
        $intercom = $this->getIntercomCgi();
        $alarm = $this->get('/cgi-bin/intercom_alarm_cgi', ['action' => 'get'], parse: ['type' => 'param']);

        return new Room(strval($intercom['ConciergeApartment']), strval($alarm['SOSCallNumber']));
    }

    public function getRelay(int $type): Relay
    {
        $intercom = $this->getIntercomCgi();

        if ($this == 1) {
            return new Relay($intercom['AltDoorOpenMode'] === 'off', intval($intercom['DoorOpenTime']));
        }

        $mode = array_key_exists('DoorOpenMode', $intercom) ? $intercom['DoorOpenMode'] : (array_key_exists('MainDoorOpenMode', $intercom) ? $intercom['MainDoorOpenMode'] : 'on');

        return new Relay($mode === 'off', intval($intercom['DoorOpenTime']));
    }

    public function getDDns(): DDns
    {
        return new DDns(true, '', 0);
    }

    /**
     * @return Gate[]
     */
    public function getGates(): array
    {
        $gate = $this->get('/cgi-bin/gate_cgi', ['action' => 'get'], parse: ['type' => 'param']);

        if ($gate['Enable'] === 'off') {
            return [];
        }

        $result = [];

        $count = intval($gate['EntranceCount']);

        for ($i = 1; $i < -$count; ++$i) {
            $result[] = new Gate(
                $gate['Address' . $i],
                intval($gate['Prefix' . $i]),
                intval($gate['BegNumber' . $i]),
                intval($gate['EndNumber' . $i])
            );
        }

        usort($result, static fn(Gate $a, Gate $b): int => $a->prefix > $b->prefix ? 1 : -1);

        return $result;
    }

    public function getUPnP(): bool
    {
        return true;
    }

    public function getIndividualLevels(): bool
    {
        $intercom = $this->getIntercomCgi();

        return array_key_exists('IndividualLevels', $intercom) && $intercom['IndividualLevels'] == 'on';
    }

    public function getAutoCollectKey(): bool
    {
        if ($this->mifare) {
            $response = $this->get('/cgi-bin/' . $this->resolver->string('mifare.cgi', 'mifareusr_cgi'), ['action' => 'get'], parse: ['type' => 'param']);

            return $response['AutoCollectKeys'] === 'on';
        }

        return false;
    }

    public function setNtp(Ntp $ntp): void
    {
        $this->get('/cgi-bin/ntp_cgi', ['action' => 'set', 'Enable' => 'on', 'ServerAddress' => $ntp->server, 'ServerPort' => $ntp->port, 'Timezone' => 21, 'AutoMode' => 'off']);
    }

    public function setStun(Stun $stun): void
    {
        $this->get('/webs/SIP1CfgEx', ['cknat' => 1, 'stunip' => $stun->server, 'stunport' => $stun->port]);
    }

    public function setSyslog(Syslog $syslog): void
    {
        $this->get('/cgi-bin/rsyslog_cgi', ['action' => 'set', 'Enable' => 'on', 'Protocol' => 'udp', 'ServerAddress' => $syslog->server, 'ServerPort' => $syslog->port, 'LogLevel' => 6]);
    }

    public function setMifare(Mifare $mifare): void
    {
        if ($this->mifare) {
            $this->get('/cgi-bin/cipher_cgi', ['action' => 'add', 'Value' => $mifare->key, 'Type' => 1, 'Index' => 1]);
            $this->get('/cgi-bin/' . $this->resolver->string('mifare.cgi', 'mifareusr_cgi'), ['action' => 'set', 'Sector' => $mifare->sector, 'AutoValidation' => 'off']);
        }
    }

    public function setRoom(Room $room): void
    {
        $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'ConciergeApartment' => $room->concierge]);
        $this->get('/cgi-bin/intercom_alarm_cgi', ['action' => 'set', 'SOSCallActive' => 'on', 'SOSCallNumber' => $room->sos]);
    }

    public function setRelay(Relay $relay, int $type): void
    {
        $value = $relay->lock ? 'off' : 'on';

        if ($type == 1) {
            $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'AltDoorOpenMode' => $value, 'DoorOpenTime' => $relay->openDuration]);

            return;
        }

        $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'DoorOpenMode' => $value, 'MainDoorOpenMode' => $value, 'DoorOpenTime' => $relay->openDuration]);
    }

    public function setDDns(DDns $dDns): void
    {
        if (!$dDns->enable) {
            $this->get('/cgi-bin/ddns_cgi', ['action' => 'set', 'Provider' => '']);
        }
    }

    public function setUPnP(bool $value): void
    {
        $this->get('/cgi-bin/upnp_cgi', ['action' => 'set', 'UpnpSearchSwitch' => $value ? 'open' : 'close']);
    }

    public function setIndividualLevels(bool $value): void
    {
        $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'IndividualLevels' => $value ? 'on' : 'off']);
    }

    public function setAutoCollectKey(bool $value): void
    {
        if ($this->mifare) {
            $this->get('/cgi-bin/' . $this->resolver->string('mifare.cgi', 'mifareusr_cgi'), ['action' => 'set', 'AutoCollectKeys' => $value ? 'on' : 'off']);
        }
    }

    /**
     * @param Gate[] $value
     * @return void
     */
    public function setGates(array $value): void
    {
        $params = [
            'action' => 'set',
            'Mode' => $this->resolver->int('wicket.mode', 1),
            'Enable' => $value !== [] ? 'on' : 'off',
            'MainDoor' => 'on',
            'AltDoor' => 'off',
            'PowerRely' => 'on',
        ];

        if ($value !== []) {
            $params['EntranceCount'] = count($value);
            $counter = count($value);

            for ($i = 0; $i < $counter; ++$i) {
                $params['Address' . ($i + 1)] = $value[$i]->address;
                $params['Prefix' . ($i + 1)] = $value[$i]->prefix;
                $params['BegNumber' . ($i + 1)] = $value[$i]->begin;
                $params['EndNumber' . ($i + 1)] = $value[$i]->end;
            }
        }

        $this->get('/cgi-bin/gate_cgi', $params);
    }

    public function setGatesMode(int $value): void
    {
        $this->get('/cgi-bin/gate_cgi', ['action' => 'set', 'Mode' => $value]);
    }

    public function setServiceMode(bool $value): void
    {
        $this->get(
            '/webs/sysSrvCodesCfgEx',
            ['ckkeyscanactive' => 0, 'cknetinfoactive' => 0, 'cknetstaticipactive' => 0, 'ckdooraddactive' => 0, 'cknetresetactive' => 0, 'ckpassresetactive' => 0, 'ckfullresetactive' => 0]
        );
    }
}