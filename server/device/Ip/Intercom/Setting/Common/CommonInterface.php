<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

interface CommonInterface
{
    public function getNtp(): Ntp;

    public function getStun(): Stun;

    public function getSyslog(): Syslog;

    public function getMifare(): Mifare;

    public function getRoom(): Room;

    public function getRelay(int $type): Relay;

    public function getDDns(): DDns;

    /**
     * @return Gate[]
     */
    public function getGates(): array;

    public function getUPnP(): bool;

    public function getIndividualLevels(): bool;

    public function getAutoCollectKey(): bool;

    public function setNtp(Ntp $ntp): void;

    public function setStun(Stun $stun): void;

    public function setSyslog(Syslog $syslog): void;

    public function setMifare(Mifare $mifare): void;

    public function setRoom(Room $room): void;

    public function setRelay(Relay $relay, int $type): void;

    public function setDDns(DDns $dDns): void;

    public function setUPnP(bool $value): void;

    public function setIndividualLevels(bool $value): void;

    public function setAutoCollectKey(bool $value): void;

    /**
     * @param Gate[] $value
     * @return void
     */
    public function setGates(array $value): void;

    public function setGatesMode(int $value): void;

    public function setServiceMode(bool $value): void;
}