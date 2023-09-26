<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\DeviceService;

class IntercomLockTask extends IntercomTask
{
    public function __construct(int $id)
    {
        parent::__construct($id, 'Синхронизация замка (' . $id . ')');
    }

    public function onTask(): bool
    {
        $households = container(HouseFeature::class);

        $domophone = $households->getDomophone($this->id);

        $device = container(DeviceService::class)->intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

        if (!$device)
            return false;

        if (!$device->ping())
            throw new RuntimeException(message: 'Устройство не доступно');

        $device->unlocked($domophone['locksAreOpen']);

        return true;
    }
}