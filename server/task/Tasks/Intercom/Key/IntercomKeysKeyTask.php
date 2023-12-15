<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Exception\DeviceException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomTask;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

class IntercomKeysKeyTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public array $keys;

    public function __construct(int $id, array $keys)
    {
        parent::__construct($id, 'Массовая синхронизация ключей на дому (' . $id . ')');

        $this->keys = $keys;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $entrances = container(HouseFeature::class)->getEntrances('houseId', $this->id);

        if (!$entrances || count($entrances) === 0)
            return false;

        foreach ($entrances as $entrance) {
            $this->entrance($entrance);
        }

        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function entrance(array $entrance): void
    {
        $domophoneId = $entrance['domophoneId'];

        $device = intercom($domophoneId);

        if (!$device)
            return;

        if (!$device->ping())
            throw new DeviceException($device, 'Устройство не доступно');

        foreach ($this->keys as $key)
            $device->addRfidDeffer($key['rfId'], $key['accessTo']);

        $device->deffer();
    }
}