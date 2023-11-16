<?php

namespace Selpol\Device\Ip\Trait;

use Selpol\Device\Exception\DeviceException;
use SensitiveParameter;
use Throwable;

trait HikVisionTrait
{
    public function getSysInfo(): array
    {
        try {
            $info = $this->get('/ISAPI/System/deviceInfo');

            return [
                'DeviceID' => $info['deviceID'],
                'DeviceModel' => $info['model'],
                'HardwareVersion' => $info['hardwareVersion'],
                'SoftwareVersion' => $info['firmwareVersion'] . ' ' . $info['firmwareReleasedDate']
            ];
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        $this->put('/Security/users/1', "<User><id>1</id><userName>$this->login</userName><password>$password</password><userLevel>Administrator</userLevel><loginPassword>$this->password</loginPassword></User>", ['Content-Type' => 'application/xml']);

        return $this;
    }
}