<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\Relay;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

class IntercomLockTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public bool $lock;

    public int $type;

    public function __construct(int $id, bool $lock, int $type)
    {
        parent::__construct($id, 'Синхронизация замка (' . $id . ', ' . ($lock ? 'Закрыто' : 'Открыто') . ', ' . ($type == 0 ? 'Основной' : 'Дополнительный') . ')');

        $this->lock = $lock;
        $this->type = $type;
    }

    public function onTask(): bool
    {
        $device = intercom($this->id);

        if (!$device instanceof IntercomDevice) {
            throw new DeviceException($device, 'Устройство не найдено');
        }

        $this->setProgress(25);

        if (!$device->ping()) {
            throw new DeviceException($device, 'Устройство не доступно');
        }

        $this->setProgress(50);

        if ($device instanceof CommonInterface) {
            $unlockTime = $device->resolver->int('clean.unlock_time', 5);

            $device->setRelay(new Relay($this->lock, $unlockTime), $this->type);
        }

        return true;
    }
}