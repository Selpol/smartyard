<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

use Selpol\Device\Exception\DeviceException;
use SensitiveParameter;
use Throwable;

trait IsTrait
{
    public function getSysInfo(): array
    {
        try {
            $info = $this->get('/system/info');
            $version = $this->get('/v2/system/versions');

            if ($version == null || !array_key_exists('opt', $version) || $version['opt'] == null) {
                $hardwareVersion = '2.5';
                $softwareVersion = '2.2.5.14.0';
            } else {
                $hardwareVersion = $version['opt']['versions']['hw']['name'];
                $softwareVersion = $version['opt']['name'];
            }

            return [
                'DeviceID' => $info['deviceID'],
                'DeviceModel' => $info['model'],

                'HardwareVersion' => $hardwareVersion,
                'SoftwareVersion' => $softwareVersion
            ];
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        $this->put('/user/change_password', ['newPassword' => $password]);

        return $this;
    }
}