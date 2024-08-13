<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Throwable;

class IntercomDeleteKeyTask extends IntercomKeyTask
{
    public string $key;

    public int $flatId;

    public function __construct(string $key, int $flatId)
    {
        parent::__construct($flatId, 'Удалить ключ (' . $key . ', ' . $flatId . ')');

        $this->key = $key;

        $this->setLogger(file_logger('task-intercom'));
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
                $this->delete($entrance->house_domophone_id, intval($flat->flat));
            }

            return true;
        }

        return false;
    }

    private function delete(int $id, int $flat): void
    {
        try {
            $device = intercom($id);

            if ($device instanceof KeyInterface) {
                if (!$device->ping()) {
                    return;
                }

                $device->removeKey(new Key($this->key, $flat));
            }
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);
        }
    }
}