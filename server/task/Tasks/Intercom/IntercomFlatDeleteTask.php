<?php

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Task\Task;
use Throwable;

class IntercomFlatDeleteTask extends Task
{
    public array $apartments;
    public array $intercoms;

    public function __construct(array $apartments, array $intercoms)
    {
        parent::__construct('Удаление квартиры (' . implode(',', $apartments) . ', ' . implode(',', $intercoms) . ')');

        $this->apartments = $apartments;
        $this->intercoms = $intercoms;
    }

    public function onTask(): bool
    {
        foreach ($this->intercoms as $index => $intercom)
            $this->delete($this->apartments[$index], $intercom);

        return false;
    }

    private function delete(int $apartment, int $intercom)
    {
        $domophone = backend('households')->getDomophone($intercom);

        if (!$domophone)
            return;

        try {
            $device = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

            $device->removeApartment($apartment);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);
        }
    }
}