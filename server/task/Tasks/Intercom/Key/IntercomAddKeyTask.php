<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;
use Throwable;

class IntercomAddKeyTask extends Task
{
    public string $key;
    public int $flatId;

    public function __construct(string $key, int $flatId)
    {
        parent::__construct('Добавить ключ (' . $key . ', ' . $flatId . ')');

        $this->key = $key;
        $this->flatId = $flatId;
    }

    public function onTask(): bool
    {
        $flat = HouseFlat::findById($this->flatId, setting: setting()->columns(['flat']));

        if (!$flat) {
            return false;
        }

        $entrances = container(HouseFeature::class)->getEntrances('flatId', $this->flatId);

        if ($entrances && count($entrances) > 0) {
            foreach ($entrances as $entrance) {
                $this->add($entrance['domophoneId'], intval($flat->flat));
            }

            return true;
        }

        return false;
    }

    private function add(int $id, int $flat): void
    {
        try {
            $device = intercom($id);

            if ($device instanceof KeyInterface) {
                if (!$device->ping()) {
                    return;
                }

                $device->addKey(new Key($this->key, $flat));
            }
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);
        }
    }
}