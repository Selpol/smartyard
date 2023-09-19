<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Selpol\Task\Tasks\Intercom\IntercomTask;

class IntercomHouseKeyTask extends IntercomTask
{
    public function __construct(int $id)
    {
        parent::__construct($id, 'Добавление ключей на дом (' . $id . ')');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $entrances = backend('households')->getEntrances('houseId', $this->id);

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
        $domophone = backend('households')->getDomophone($entrance['domophoneId']);

        if (!$domophone)
            return;

        $domophoneId = $entrance['domophoneId'];

        $device = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);
        if (!$device)
            return;

        if (!$device->ping())
            throw new RuntimeException(message: 'Устройство не доступно');

        $flats = backend('households')->getFlats('houseId', $entrance['houseId']);

        foreach ($flats as $flat) {
            $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                return $entrance['domophoneId'] == $domophoneId;
            });

            if ($flat_entrances) {
                $apartment = $flat['flat'];

                foreach ($flat_entrances as $flat_entrance)
                    if ($flat_entrance['apartment'] != $apartment)
                        $apartment = $flat_entrance['apartment'];

                $keys = backend('households')->getKeys('flatId', $flat['flatId']);

                foreach ($keys as $key)
                    $device->addRfidDeffer($key['rfId'], $apartment);
            }
        }

        $device->deffer();
    }
}