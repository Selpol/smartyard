<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Exception\DeviceException;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;
use Selpol\Task\TaskUniqueInterface;

class IntercomUnlockTask extends IntercomTask implements TaskUniqueInterface
{
    public bool $lock;

    public function __construct(int $id, bool $lock)
    {
        parent::__construct($id, 'Синхронизация замка (' . $id . ', ' . ($lock ? 'Открыто' : 'Закрыто') . ')');

        $this->lock = $lock;
    }

    public function unique(): array
    {
        return [IntercomUnlockTask::class, $this->id, $this->lock];
    }

    public function onTask(): bool
    {
        $intercom = container(DeviceIntercomRepository::class)->findById($this->id);
        $device = intercom($intercom->house_domophone_id);

        if (!$device->ping())
            throw new DeviceException($device, 'Устройство не доступно');

        $device->unlock($this->lock);

        return true;
    }
}