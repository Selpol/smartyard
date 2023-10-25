<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Exception\DeviceException;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

class IntercomUnlockTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public $taskUniqueTtl = 60;

    public bool $lock;

    public function __construct(int $id, bool $lock)
    {
        parent::__construct($id, 'Синхронизация замка (' . $id . ', ' . ($lock ? 'Открыто' : 'Закрыто') . ')');

        $this->lock = $lock;
    }

    public function onTask(): bool
    {
        $intercom = container(DeviceIntercomRepository::class)->findById($this->id);
        $device = intercom($intercom->house_domophone_id);

        $this->setProgress(25);

        if (!$device->ping())
            throw new DeviceException($device, 'Устройство не доступно');

        $this->setProgress(50);

        $device->unlock($this->lock);

        return true;
    }
}