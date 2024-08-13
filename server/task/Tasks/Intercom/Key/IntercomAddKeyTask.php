<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Throwable;

class IntercomAddKeyTask extends IntercomKeyTask
{
    public string $key;
    public int $flatId;

    public function __construct(string $key, int $flatId)
    {
        parent::__construct($flatId, 'Добавить ключ (' . $key . ', ' . $flatId . ')');

        $this->key = $key;
    }

    public function onTask(): bool
    {
        $flat = $this->getFlat();

        if (!$flat) {
            return false;
        }

        $entrances = $this->getEntrances();

        if ($entrances && count($entrances) > 0) {
            foreach ($entrances as $entrance) {
                $this->add($entrance->house_domophone_id, intval($flat->flat));
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