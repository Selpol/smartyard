<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Common\DDns;
use Selpol\Device\Ip\Intercom\Setting\Common\Gate;
use Selpol\Device\Ip\Intercom\Setting\Common\Mifare;
use Selpol\Device\Ip\Intercom\Setting\Common\Ntp;
use Selpol\Device\Ip\Intercom\Setting\Common\Relay;
use Selpol\Device\Ip\Intercom\Setting\Common\Room;
use Selpol\Device\Ip\Intercom\Setting\Common\Stun;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;
use Throwable;

trait CommonTrait
{

    public function getNtp(): Ntp
    {
        $response = $this->get('/system/settings');

        return new Ntp(count($response['ntp']) > 0 ? $response['ntp'][0] : '127.0.0.1', 123, $response['tz']);
    }

    public function getStun(): Stun
    {
        return new Stun('127.0.0.1', 0);
    }

    public function getSyslog(): Syslog
    {
        try {
            $response = $this->client->send(client_request('GET', $this->uri . '/system/files/rsyslogd.conf'), $this->clientOption);
            $contents = $response->getBody()->getContents();
            $lines = explode(PHP_EOL, $contents);

            if (str_starts_with($lines[count($lines) - 1], '@')) {
                $value = explode(':', $lines[count($lines) - 1]);

                $server = $value[0];
                $port = explode(';', $value[1])[0];
            } else {
                $server = '127.0.0.1';
                $port = 0;
            }

            return new Syslog($server, $port);
        } catch (Throwable) {
            return new Syslog('127.0.0.1', 0);
        }
    }

    public function getMifare(): Mifare
    {
        if ($this->model->mifare) {
            $response = $this->get('/key/settings');

            return new Mifare($response['encryption']['enabled'] == true, $response['encryption']['key_auth'] ?? '', intval($response['encryption']['sector']) ?? 0);
        }

        return new Mifare(false, '', 0);
    }

    public function getRoom(): Room
    {
        $response = $this->get('/panelCode/settings');

        return new Room(strval($response['consiergeRoom']), strval($response['sosRoom']));
    }

    public function getRelay(): Relay
    {
        $settings = $this->get('/relay/settings');
        $relay = $this->get('/relay/1/settings');

        return new Relay(!$settings['alwaysOpen'], $relay['switchTime']);
    }

    public function getDDns(): DDns
    {
        $response = $this->get('/v1/ddns');

        return new DDns($response['enabled'], $response['server']['address'], $response['server']['port']);
    }

    public function getUPnP(): bool
    {
        return false;
    }

    public function getAutoCollectKey(): bool
    {
        $response = $this->get('/key/settings');

        return $response['autocollect']['enabled'];
    }

    public function getGates(): array
    {
        $response = $this->get('/gate/settings');

        if (!$response['gateMode'] || !$response['prefixHouse']) {
            return [];
        }

        if (!$response['direct']['mode']) {
            return [new Gate('', 0, 0, 0)];
        }

        $result = [];

        foreach ($response['direct']['rules'] as $prefix => $rules) {
            foreach ($rules as $range => $address) {
                $slice = preg_split('-', $range);

                $result[] = new Gate($address, intval($prefix), intval($slice[0]), intval($slice[1]));
            }
        }

        usort($result, static fn(Gate $a, Gate $b) => $a->prefix > $b->prefix ? 1 : -1);

        return $result;
    }

    public function setNtp(Ntp $ntp): void
    {
        $this->put('/system/settings', ['tz' => $ntp->timezone, 'ntp' => [$ntp->server]]);
    }

    public function setStun(Stun $stun): void
    {
    }

    public function setSyslog(Syslog $syslog): void
    {
        $this->client->send(
            client_request('PUT', $this->uri . '/system/files/rsyslogd.conf')
                ->withHeader('Content-Type', 'text/plain')
                ->withBody(stream($this->getSyslogConfigHelp($syslog->server, $syslog->port))),
            $this->clientOption
        );
    }

    public function setMifare(Mifare $mifare): void
    {
        if ($this->model->mifare) {
            $this->put('/key/settings', [
                'encryption' => [
                    'enabled' => $mifare->enable,
                    'key_type' => 'A',
                    'key_auth' => $mifare->key,
                    'sector' => $mifare->sector,
                    'increment' => ['enabled' => false, 'block' => 0, 'openByError' => false]
                ]
            ]);
        }
    }

    public function setRoom(Room $room): void
    {
        $this->put('/panelCode/settings', ['consiergeRoom' => $room->concierge, 'sosRoom' => $room->sos]);
    }

    public function setRelay(Relay $relay): void
    {
        $this->put('relay/settings', ['alwaysOpen' => !$relay->lock]);

        $relays = $this->get('/relay/info');

        foreach ($relays as $value) {
            $this->put('/relay/' . $value . '/settings', ['switchTime' => $relay->openDuration]);
        }
    }

    public function setDDns(DDns $dDns): void
    {
        $this->put('/v1/ddns', ['enabled' => $dDns->enable, 'server' => $dDns->server, 'port' => $dDns->port]);
    }

    public function setUPnP(bool $value): void
    {
    }

    public function setAutoCollectKey(bool $value): void
    {
        $this->put('/key/settings', ['autocollect' => ['enabled' => $value]]);
    }

    /**
     * @param Gate[] $value
     * @return void
     */
    public function setGates(array $value): void
    {
        if (count($value) > 0) {
            $direct = ['mode' => filter_var($value[0]->address, FILTER_VALIDATE_IP) !== false];

            if ($direct['mode']) {
                $direct['rules'] = [];

                foreach ($value as $gate) {
                    if (filter_var($gate->address, FILTER_VALIDATE_IP) !== false) {
                        $direct['rules'][$gate->prefix] = [$gate->begin . '-' . $gate->end => $gate->address];
                    }
                }
            }
        } else {
            $direct = ['mode' => false];
        }

        $this->put('/gate/settings', ['gateMode' => count($value) > 0, 'prefixHouse' => count($value) > 0, 'direct' => $direct]);
    }

    private function getSyslogConfigHelp(string $server, int $port): string
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