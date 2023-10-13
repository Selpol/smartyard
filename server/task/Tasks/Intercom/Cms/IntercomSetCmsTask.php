<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Cms;

use Selpol\Device\Exception\DeviceException;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;
use Selpol\Task\Tasks\Intercom\IntercomTask;

class IntercomSetCmsTask extends IntercomTask
{
    public string $cms;

    public function __construct(int $id, string $cms)
    {
        parent::__construct($id, 'Установка CMS (' . $id . ', ' . $cms . ')');

        $this->cms = $cms;
    }

    public function onTask(): bool
    {
        $intercom = container(DeviceIntercomRepository::class)->findById($this->id);
        $device = intercom($intercom->house_domophone_id);

        if (!$device->ping())
            throw new DeviceException($device, 'Устройство не доступно');

        $device->setCmsModel($this->cms);

        return true;
    }
}