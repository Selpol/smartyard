<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\DeviceService;

class IntercomLockTask extends IntercomTask
{
    public bool $lock;

    public function __construct(bool $lock, int $id)
    {
        parent::__construct($id, 'Синхронизация замка (' . $id . ', ' . ($lock ? 'Закрыто' : 'Открыто') . ')');

        $this->lock = $lock;
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

        $device->unlocked($this->lock);

        return true;
    }
}