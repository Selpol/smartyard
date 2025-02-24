<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Key;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Task\Task;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;

class IntercomFlatKeyTask extends Task implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public int $id;

    public function __construct(int $id)
    {
        parent::__construct('Синхронизация ключей на квартире (' . $id . ')');

        $this->id = $id;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $flat = HouseFlat::findById($this->id);

        if (!$flat) {
            return false;
        }

        $keys = $flat->keys;
        $entrances = $flat->entrances;

        if (!$entrances || count($entrances) === 0) {
            return false;
        }

        foreach ($entrances as $entrance) {
            try {
                $this->entrance($flat, $entrance, $keys);
            } catch (Throwable $throwable) {
                $this->logger?->error($throwable);
            }
        }

        return true;
    }

    /**
     * @param HouseFlat $flat
     * @param HouseEntrance $entrance
     * @param HouseKey[] $keys
     * @return void
     */
    private function entrance(HouseFlat $flat, HouseEntrance $entrance, array $keys): void
    {
        $device = intercom($entrance->house_domophone_id);

        if (!$device) {
            return;
        }

        if (!$device->ping()) {
            return;
        }

        if (!($device instanceof KeyInterface)) {
            return;
        }

        foreach ($keys as $key) {
            $device->addKey(new Key($key->rfid, intval($flat->flat)));
        }
    }
}