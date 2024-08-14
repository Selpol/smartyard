<?php

namespace Selpol\Task\Tasks\Intercom\Flat;

use RuntimeException;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Task;
use Throwable;

class IntercomDeleteFlatTask extends Task
{
    public array $entrances;

    public function __construct(int $subscriberId, array $entrances)
    {
        parent::__construct('Удаления квартиры (' . $subscriberId . ')');

        $this->entrances = $entrances;

        $this->setLogger(file_logger('task-intercom'));
    }

    public function onTask(): bool
    {
        foreach ($this->entrances as $entrance) {
            $this->delete($entrance[0], $entrance[1]);
        }

        return false;
    }

    private function delete(int $apartment, int $intercom): void
    {
        try {
            $device = intercom($intercom);

            if ($device instanceof ApartmentInterface) {
                if (!$device->ping()) {
                    return;
                }

                $device->removeApartment($apartment);
            }
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            if ($throwable instanceof KernelException) {
                throw $throwable;
            }

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}