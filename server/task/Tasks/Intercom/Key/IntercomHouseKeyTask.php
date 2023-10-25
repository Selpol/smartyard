<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Exception\DeviceException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomTask;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

class IntercomHouseKeyTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public function __construct(int $id)
    {
        parent::__construct($id, 'Синхронизация ключей на дому (' . $id . ')');
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
            throw new DeviceException($device, message: 'Устройство не доступно');

        $flats = container(HouseFeature::class)->getFlats('houseId', $entrance['houseId']);

        foreach ($flats as $flat) {
            $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                return $entrance['domophoneId'] == $domophoneId;
            });

            if ($flat_entrances) {
                $apartment = $flat['flat'];

                foreach ($flat_entrances as $flat_entrance)
                    if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment)
                        $apartment = $flat_entrance['apartment'];

                $keys = container(HouseFeature::class)->getKeys('flatId', $flat['flatId']);

                foreach ($keys as $key)
                    $device->addRfidDeffer($key['rfId'], $apartment);
            }
        }

        $device->deffer();
    }
}