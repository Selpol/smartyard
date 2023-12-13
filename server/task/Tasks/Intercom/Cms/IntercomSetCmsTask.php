<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Cms;

use Selpol\Device\Exception\DeviceException;
use Selpol\Task\Tasks\Intercom\IntercomTask;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

class IntercomSetCmsTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public string $cms;

    public function __construct(int $id, string $cms)
    {
        parent::__construct($id, 'Установка CMS (' . $id . ', ' . $cms . ')');

        $this->cms = $cms;
    }

    public function onTask(): bool
    {
        $device = intercom($this->id);

        if ($device?->ping() !== true)
            throw new DeviceException($device, 'Устройство не доступно');

        $device->setCmsModel($this->cms);

        return true;
    }
}