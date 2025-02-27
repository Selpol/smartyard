<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomTask;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;

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

        if (!$entrances || count($entrances) === 0) {
            return false;
        }

        foreach ($entrances as $entrance) {
            try {
                $this->entrance($entrance);
            } catch (Throwable $throwable) {
                $this->logger?->error($throwable);
            }
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

        if (!$device->ping()) {
            return;
        }

        if (!$device instanceof IntercomDevice) {
            return;
        }

        if (!$device instanceof KeyInterface) {
            return;
        }

        $flats = container(HouseFeature::class)->getFlats('houseId', $entrance['houseId']);

        foreach ($flats as $flat) {
            $flat_entrances = array_filter($flat['entrances'], fn(array $entrance): bool => $entrance['domophoneId'] == $domophoneId);

            if ($flat_entrances && $flat_entrances !== []) {
                $apartment = $flat['flat'];

                foreach ($flat_entrances as $flat_entrance) {
                    if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment) {
                        $apartment = $flat_entrance['apartment'];
                    }
                }

                $keys = HouseKey::fetchAll(criteria()->equal('access_type', 2)->equal('access_to', $flat['flatId']));

                foreach ($keys as $key) {
                    $device->addKey(new Key($key->rfid, $apartment));
                }
            }
        }
    }
}