<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Service\DeviceService;

class IntercomLockTask extends IntercomTask
{
    public bool $lock;

    public function __construct(int $id, bool $lock)
    {
        parent::__construct($id, 'Синхронизация замка (' . $id . ', ' . ($lock ? 'Закрыто' : 'Открыто') . ')');

        $this->lock = $lock;
    }

    public function onTask(): bool
    {
        $device = container(DeviceService::class)->intercomById($this->id);

        if (!$device)
            return false;

        if (!$device->ping())
            throw new RuntimeException(message: 'Устройство не доступно');

        $device->unlocked($this->lock);

        return true;
    }
}