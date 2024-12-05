<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

readonly class InfoDevice
{
    public string $deviceId;
    public string $deviceModel;

    public string $hardwareVersion;
    public string $softwareVersion;

    public function __construct(string $deviceId, string $deviceModel, string $hardwareVersion, string $softwareVersion)
    {
        $this->deviceId = $deviceId;
        $this->deviceModel = $deviceModel;

        $this->hardwareVersion = $hardwareVersion;
        $this->softwareVersion = $softwareVersion;
    }
}
