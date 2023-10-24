<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
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
        $flat = container(HouseFeature::class)->getFlat($this->flatId);

        if (!$flat)
            return false;

        $entrances = container(HouseFeature::class)->getEntrances('flatId', $this->flatId);

        if ($entrances && count($entrances) > 0) {
            foreach ($entrances as $entrance) {
                $id = $entrance['domophoneId'];

                if ($id)
                    $this->add($id, $flat['flat']);
            }

            return true;
        }

        return false;
    }

    private function add(int $id, int $flat): void
    {
        try {
            $device = intercom($id);

            if (!$device->ping())
                throw new DeviceException($device, message: 'Устройство не доступно');

            $device->addRfid($this->key, $flat);
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}