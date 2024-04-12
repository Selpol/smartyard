<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

interface CommonInterface
{
    public function getNtp(): Ntp;

    public function getStun(): Stun;

    public function getSyslog(): Syslog;

    public function getMifare(): Mifare;

    public function getRoom(): Room;

    public function setNtp(Ntp $ntp): void;

    public function setStun(Stun $stun): void;

    public function setSyslog(Syslog $syslog): void;

    public function setMifare(Mifare $mifare): void;

    public function setRoom(Room $room): void;
}