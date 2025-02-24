<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomTask;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;

class IntercomKeysKeyTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public array $keys;

    public function __construct(int $id, array $keys)
    {
        parent::__construct($id, 'Массовая синхронизация ключей на дому (' . $id . ',' . count($keys) . ')');

        $this->keys = $keys;
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
        $device = intercom($entrance['domophoneId']);

        if (!$device instanceof IntercomDevice) {
            return;
        }

        if (!$device->ping()) {
            return;
        }

        if (!$device instanceof KeyInterface) {
            return;
        }

        /** @var array<int, int> $flats */
        $flats = [];

        foreach ($this->keys as $key) {
            if (!array_key_exists($key['accessTo'], $flats)) {
                $flats[$key['accessTo']] = HouseFlat::findById($key['accessTo'], setting: setting()->columns(['flat'])->nonNullable())->flat;
            }

            $device->addKey(new Key($key['rfId'], $flats[$key['accessTo']]));
        }
    }
}