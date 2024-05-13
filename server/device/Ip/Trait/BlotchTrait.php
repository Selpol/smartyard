<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

trait BlotchTrait
{
    public function getSysInfo(): array
    {
        return [
            'DeviceID' => 'BLOTCH',
            'DeviceModel' => 'BLOTCH',

            'HardwareVersion' => '1',
            'SoftwareVersion' => '1'
        ];
    }
}