<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use DateTimeImmutable;
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
        $time = $this->get('/ISAPI/System/time');
        $servers = $this->get('/ISAPI/System/time/ntpServers/1');

        if (is_null($servers['ipAddress'])) {
            return new Ntp('', 123, $time['timeZone'] ?? '');
        }

        return new Ntp($servers['ipAddress'], intval($servers['port']), $time['timeZone']);
    }

    public function getStun(): Stun
    {
        return new Stun('', 0);
    }

    public function getSyslog(): Syslog
    {
        $response = $this->get('/ISAPI/Event/notification/httpHosts');
        $notification = $response['HttpHostNotification'];

        return new Syslog($notification['ipAddress'] ?? '', intval($notification['portNo']) ?? 0);
    }

    public function getMifare(): Mifare
    {
        return new Mifare(false, '', 0);
    }

    public function getRoom(): Room
    {
        return new Room('', '');
    }

    public function getRelay(int $type): Relay
    {
        $door = $this->get('/ISAPI/AccessControl/Door/param/1');
        $param = $door['DoorParam'];

        return new Relay($param['magneticType'] === 'alwaysClose', intval($param['openDuration']));
    }

    public function getDDns(): DDns
    {
        return new DDns(false, '', 0);
    }

    /**
     * @return Gate[]
     */
    public function getGates(): array
    {
        return [];
    }

    public function getUPnP(): bool
    {
        return false;
    }

    public function getIndividualLevels(): bool
    {
        return true;
    }

    public function getAutoCollectKey(): bool
    {
        return false;
    }

    public function setNtp(Ntp $ntp): void
    {
        $date = new DateTimeImmutable();
        $time = str_replace(' ', 'T', $date->format('Y-m-d H:i:s'));

        $this->put('/ISAPI/System/time', "<Time><timeMode>NTP</timeMode><localTime>" . $time . "</localTime>><timeZone>" . $ntp->timezone . "</timeZone></Time>", ['Content-Type' => 'application/xml']);
        $this->put('/ISAPI/System/time/ntpServers/1', "<NTPServer><id>1</id><addressingFormatType>ipaddress</addressingFormatType><ipAddress>" . $ntp->server . "</ipAddress><portNo>" . $ntp->port . "</portNo><synchronizeInterval>60</synchronizeInterval></NTPServer>", ['Content-Type' => 'application/xml']);
    }

    public function setStun(Stun $stun): void
    {

    }

    public function setSyslog(Syslog $syslog): void
    {
        $this->put('/ISAPI/Event/notification/httpHosts', "<HttpHostNotificationList><HttpHostNotification><id>1</id><url>/</url><protocolType>HTTP</protocolType><parameterFormatType>XML</parameterFormatType><addressingFormatType>ipaddress</addressingFormatType><ipAddress>" . $syslog->server . "</ipAddress><portNo>" . $syslog->port . "</portNo><httpAuthenticationMethod>none</httpAuthenticationMethod></HttpHostNotification></HttpHostNotificationList>", ['Content-Type' => 'application/xml']);
    }

    public function setMifare(Mifare $mifare): void
    {

    }

    public function setRoom(Room $room): void
    {

    }

    public function setRelay(Relay $relay, int $type): void
    {
        $this->put('/ISAPI/AccessControl/Door/param/1', "<DoorParam><doorName>Door1</doorName><openDuration>" . $relay->openDuration . "</openDuration><magneticType>" . ($relay->lock ? 'alwaysClose' : 'alwaysOpen') . "</magneticType>></DoorParam>", ['Content-Type' => 'application/xml']);
    }

    public function setDDns(DDns $dDns): void
    {

    }

    public function setUPnP(bool $value): void
    {

    }

    public function setIndividualLevels(bool $value): void
    {

    }

    public function setAutoCollectKey(bool $value): void
    {

    }

    /**
     * @param Gate[] $value
     * @return void
     */
    public function setGates(array $value): void
    {

    }

    public function setGatesMode(int $value): void
    {

    }
}