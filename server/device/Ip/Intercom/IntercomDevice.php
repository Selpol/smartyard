<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\IpDevice;

abstract class IntercomDevice extends IpDevice
{
    public function getSipStatus(): bool
    {
        throw new DeviceException($this);
    }

    public function addCmsDefer(int $index, int $dozen, int $unit, int $apartment): void
    {
        throw new DeviceException($this);
    }

    public function addCode(int $code, int $apartment): void
    {
        throw new DeviceException($this);
    }

    public function removeCode(int $apartment): void
    {
        throw new DeviceException($this);
    }

    public function addRfid(string $code, int $apartment): void
    {
        throw new DeviceException($this);
    }

    public function addRfidDeffer(string $code, int $apartment): void
    {
        throw new DeviceException($this);
    }

    public function removeRfid(string $code): void
    {
        throw new DeviceException($this);
    }

    public function addApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        throw new DeviceException($this);
    }

    public function addApartmentDeffer(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        throw new DeviceException($this);
    }

    public function removeApartment(int $apartment): void
    {
        throw new DeviceException($this);
    }

    public function setApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): static
    {
        throw new DeviceException($this);
    }

    public function setApartmentLevels(int $apartment, int $answer, int $quiescent): static
    {
        throw new DeviceException($this);
    }

    public function setGate(bool $value): static
    {
        throw new DeviceException($this);
    }

    public function setMotionDetection(int $sensitivity, int $left, int $top, int $width, int $height): static
    {
        throw new DeviceException($this);
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        throw new DeviceException($this);
    }

    public function setStun(string $server, int $port): static
    {
        throw new DeviceException($this);
    }

    public function setSyslog(string $server, int $port): static
    {
        throw new DeviceException($this);
    }

    public function setMifare(string $key, int $sector): static
    {
        throw new DeviceException($this);
    }

    public function setAudioLevels(array $levels): static
    {
        throw new DeviceException($this);
    }

    public function setCallTimeout(int $value): static
    {
        throw new DeviceException($this);
    }

    public function setTalkTimeout(int $value): static
    {
        throw new DeviceException($this);
    }

    public function setCmsLevels(array $levels): static
    {
        throw new DeviceException($this);
    }

    public function setCmsModel(string $value): static
    {
        throw new DeviceException($this);
    }

    public function setConcierge(int $value): static
    {
        throw new DeviceException($this);
    }

    public function setSos(int $value): static
    {
        throw new DeviceException($this);
    }

    public function setPublicCode(int $code): static
    {
        throw new DeviceException($this);
    }

    public function setDtmf(string $code1, string $code2, string $code3, string $codeOut): static
    {
        throw new DeviceException($this);
    }

    public function setUnlockTime(int $time): static
    {
        throw new DeviceException($this);
    }

    public function setVideoOverlay(string $title): static
    {
        throw new DeviceException($this);
    }

    public function unlocked(bool $value): void
    {
        throw new DeviceException($this);
    }

    public function open(int $value): void
    {
        throw new DeviceException($this);
    }

    public function clearApartment(): void
    {
        throw new DeviceException($this);
    }

    public function clearCms(string $model): void
    {
        throw new DeviceException($this);
    }

    public function clearRfid(): void
    {
        throw new DeviceException($this);
    }

    public function clearCode(): void
    {
        throw new DeviceException($this);
    }

    public function clean(string $sip_server, string $ntp_server, string $syslog_server, string $sip_username, int $sip_port, int $ntp_port, int $syslog_port, string $main_door_dtmf, array $audio_levels, array $cms_levels, string $cms_model,): void
    {
        $this->setSyslog($syslog_server, $syslog_port);
        $this->setUnlockTime(5);
        $this->setPublicCode(0);
        $this->setCallTimeout(45);
        $this->setTalkTimeout(90);
        $this->setAudioLevels($audio_levels);
        $this->setCmsLevels($cms_levels);
        $this->setNtp($ntp_server, $ntp_port);
        $this->setSip($sip_username, $this->password, $sip_server, $sip_port);
        $this->setDtmf($main_door_dtmf, '2', '3', '1');
        $this->clearRfid();
        $this->clearApartment();
        $this->setSos(9998);
        $this->setConcierge(9999);
        $this->setCmsModel($cms_model);
        $this->setGate(false);
    }

    public function defferCmses(): void
    {
        throw new DeviceException($this);
    }

    public function defferRfids(): void
    {
        throw new DeviceException($this);
    }

    public function defferApartments(): void
    {
        throw new DeviceException($this);
    }

    public function deffer(): void
    {
        throw new DeviceException($this);
    }
}