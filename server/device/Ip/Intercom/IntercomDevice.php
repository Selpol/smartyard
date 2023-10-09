<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\IpDevice;
use Selpol\Http\Uri;

abstract class IntercomDevice extends IpDevice
{
    public IntercomModel $model;

    public function __construct(Uri $uri, string $password, IntercomModel $model)
    {
        parent::__construct($uri, $password);

        $this->model = $model;
    }

    public function getSipStatus(): bool
    {
        return false;
    }

    public function getLineDialStatus(int $apartment): int
    {
        return 0;
    }

    public function getAllLineDialStatus(int $from, int $to): array
    {
        return [];
    }

    public function getRfids(): array
    {
        return [];
    }

    public function addCms(int $index, int $dozen, int $unit, int $apartment): void
    {
    }

    public function addCmsDefer(int $index, int $dozen, int $unit, int $apartment): void
    {
        $this->addCms($index, $dozen, $unit, $apartment);
    }

    public function addCode(int $code, int $apartment): void
    {
    }

    public function removeCode(int $apartment): void
    {
    }

    public function addRfid(string $code, int $apartment): void
    {
    }

    public function addRfidDeffer(string $code, int $apartment): void
    {
        $this->addRfid($code, $apartment);
    }

    public function removeRfid(string $code, int $apartment): void
    {
    }

    public function addApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
    }

    public function addApartmentDeffer(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): void
    {
        $this->addApartment($apartment, $handset, $sipNumbers, $levels, $code);
    }

    public function removeApartment(int $apartment): void
    {
    }

    public function setApartment(int $apartment, bool $handset, array $sipNumbers, array $levels, int $code): static
    {
        return $this;
    }

    public function setApartmentLevels(int $apartment, int $answer, int $quiescent): static
    {
        return $this;
    }

    public function setGate(array $value): static
    {
        return $this;
    }

    public function setMotionDetection(int $sensitivity, int $left, int $top, int $width, int $height): static
    {
        return $this;
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        return $this;
    }

    public function setStun(string $server, int $port): static
    {
        return $this;
    }

    public function setSyslog(string $server, int $port): static
    {
        return $this;
    }

    public function setMifare(string $key, int $sector): static
    {
        return $this;
    }

    public function setAudioLevels(array $levels): static
    {
        return $this;
    }

    public function setCallTimeout(int $value): static
    {
        return $this;
    }

    public function setTalkTimeout(int $value): static
    {
        return $this;
    }

    public function setCmsLevels(array $levels): static
    {
        return $this;
    }

    public function setCmsModel(string $value): static
    {
        return $this;
    }

    public function setConcierge(int $value): static
    {
        return $this;
    }

    public function setSos(int $value): static
    {
        return $this;
    }

    public function setPublicCode(int $code): static
    {
        return $this;
    }

    public function setDtmf(string $code1, string $code2, string $code3, string $codeOut): static
    {
        return $this;
    }

    public function setEcho(bool $value): static
    {
        return $this;
    }

    public function setUnlockTime(int $time): static
    {
        return $this;
    }

    public function setDisplayText(string $title): static
    {
        return $this;
    }

    public function setVideoOverlay(string $title): static
    {
        return $this;
    }

    public function unlocked(bool $value): void
    {
    }

    public function open(int $value): void
    {
    }

    public function clearApartment(): void
    {
    }

    public function clearCms(string $model): void
    {
    }

    public function clearRfid(): void
    {
    }

    public function clean(string $sip_server, string $ntp_server, string $syslog_server, string $sip_username, int $sip_port, int $ntp_port, int $syslog_port, string $main_door_dtmf, array $audio_levels, array $cms_levels, ?string $cms_model): void
    {
        $this->setSyslog($syslog_server, $syslog_port);
        $this->setUnlockTime(5);
        $this->setPublicCode(0);
        $this->setCallTimeout(45);
        $this->setTalkTimeout(90);
        //$this->setAudioLevels($audio_levels);
        $this->setCmsLevels($cms_levels);
        $this->setNtp($ntp_server, $ntp_port);
        $this->setSip($sip_username, $this->password, $sip_server, $sip_port);
        $this->setDtmf($main_door_dtmf, '2', '3', '1');
        $this->setEcho(false);
        $this->clearRfid();
        $this->clearApartment();
        $this->setSos(9998);
        $this->setConcierge(9999);
        $this->setCmsModel($cms_model ?? '');
        $this->setGate([]);
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
}