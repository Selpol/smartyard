<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use RuntimeException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Http\Exception\HttpException;
use Selpol\Task\Task;
use Throwable;

class IntercomDeleteKeyTask extends Task
{
    public string $key;

    public int $flatId;

    public function __construct(string $key, int $flatId)
    {
        parent::__construct('Удалить ключ (' . $key . ', ' . $flatId . ')');

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
                    $this->delete($id);
            }

            return true;
        }

        return false;
    }

    private function delete(int $id): void
    {
        try {
            $device = intercom($id);

            if (!$device->ping())
                throw new HttpException(message: 'Устройство не доступно');

            $flat = container(HouseFeature::class)->getFlat($this->flatId);

            $device->removeRfid($this->key, $flat['flat']);
        } catch (Throwable $throwable) {
            if ($throwable instanceof HttpException)
                throw $throwable;

            file_logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}