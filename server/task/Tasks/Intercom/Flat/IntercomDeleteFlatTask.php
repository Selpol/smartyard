<?php

namespace Selpol\Task\Tasks\Intercom\Flat;

use RuntimeException;
use Selpol\Task\Task;
use Throwable;

class IntercomDeleteFlatTask extends Task
{
    public array $entrances;

    public function __construct(int $flatId, array $entrances)
    {
        parent::__construct('Удаления квартиры (' . $flatId . ')');

        $this->entrances = $entrances;
    }

    public function onTask(): bool
    {
        foreach ($this->entrances as $entrance)
            $this->delete($entrance[0], $entrance[1]);

        return false;
    }

    private function delete(int $apartment, int $intercom): void
    {
        try {
            $device = intercom($intercom);

            if (!$device->ping())
                throw new RuntimeException('Устройство не доступно');

            $device->removeApartment($apartment);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}