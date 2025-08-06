<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Auto;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\InfoDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigKey;
use Throwable;

class AutoIntercom extends IntercomDevice
{
    public string $login = 'admin';

    public function specification(): string
    {
        $deviceModel = null;

        try {
            $info = $this->getIsSysInfo();

            if ($info->deviceModel) {
                $deviceModel = $info->deviceModel;
            }
        } catch (Throwable) {
        }

        if ($deviceModel == null) {
            try {
                $info = $this->getBewardSysInfo();

                if ($info->deviceModel) {
                    $deviceModel = $info->deviceModel;
                }
            } catch (Throwable) {
            }
        }

        if ($deviceModel == null) {
            try {
                $info = $this->getHikVisionSysInfo();

                if ($info->deviceModel) {
                    $deviceModel = $info->deviceModel;
                }
            } catch (Throwable) {
            }
        }

        $models = [
            ConfigKey::AutoIs1->value => 'is_1',
            ConfigKey::AutoIs5->value => 'is_5',
            ConfigKey::AutoDks->value => 'beward_dks',
            ConfigKey::AutoDs->value => 'beward_ds',
            ConfigKey::AutoHik->value => 'hikvision',
        ];

        if ($deviceModel) {
            foreach ($models as $key => $model) {
                if ($this->include($key, $deviceModel)) {
                    return $model;
                }
            }

            $model = $this->load($deviceModel);

            if ($model) {
                return $model;
            }
        }

        throw new DeviceException($this, 'Не удалось определить модель устройства ' . $deviceModel);
    }

    private function include(string $key, string $deviceModel): bool
    {
        $values = explode(',', $this->resolver->string($key, ''));

        for ($i = 0; $i < count($values); $i++) {
            if ($values[$i] == $deviceModel) {
                return true;
            }
        }

        return false;
    }

    private function load(string $deviceModel): ?string
    {
        $intercom = DeviceIntercom::fetch(criteria()->equal('device_model', $deviceModel), setting()->columns(['model']));

        if ($intercom) {
            return $intercom->model;
        }

        return null;
    }

    private function getIsSysInfo(): InfoDevice
    {
        try {
            $info = $this->get('/system/info');
            $version = $this->get('/v2/system/versions');

            if ($info == null) {
                throw new DeviceException($this, 'Не удалось получить информацию об устройстве');
            }

            if ($version == null || !array_key_exists('opt', $version) || $version['opt'] == null) {
                $hardwareVersion = '2.5';
                $softwareVersion = '2.2.5.14.0';
            } else {
                $hardwareVersion = $version['opt']['versions']['hw']['name'];
                $softwareVersion = $version['opt']['name'];
            }

            return new InfoDevice($info['deviceID'], $info['model'], $hardwareVersion, $softwareVersion, $info['mac']);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    private function getBewardSysInfo(): InfoDevice
    {
        try {
            $response = $this->get('/cgi-bin/systeminfo_cgi', ['action' => 'get'], parse: ['type' => 'param']);

            return new InfoDevice($response['DeviceID'], $response['DeviceModel'], $response['HardwareVersion'], $response['SoftwareVersion'], null);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    private function getHikVisionSysInfo(): InfoDevice
    {
        try {
            $info = $this->get('/ISAPI/System/deviceInfo');

            if ($info === null) {
                throw new DeviceException($this, 'Не удалось получить информацию об устройстве');
            }

            $serial = strlen($info['serialNumber']) > 9 ? substr($info['serialNumber'], -9) : $info['serialNumber'];

            return new InfoDevice(
                $serial,
                $info['model'],
                $info['hardwareVersion'],
                $info['firmwareVersion'] . ' ' . $info['firmwareReleasedDate'],
                null
            );
        } catch (Throwable $throwable) {
            if ($throwable instanceof DeviceException) {
                throw $throwable;
            }

            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }
}
