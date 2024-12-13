<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

readonly class InfoDevice
{
    public string $deviceId;
    public string $deviceModel;

    public string $hardwareVersion;
    public string $softwareVersion;

    public ?string $mac;

    public function __construct(string $deviceId, string $deviceModel, string $hardwareVersion, string $softwareVersion, ?string $mac)
    {
        $this->deviceId = $deviceId;
        $this->deviceModel = $deviceModel;

        $this->hardwareVersion = $hardwareVersion;
        $this->softwareVersion = $softwareVersion;

        $this->mac = $mac;
    }
}
