<?php

namespace Selpol\Task\Tasks\Intercom;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Task\Task;
use Throwable;

class IntercomKeyTask extends Task
{
    public string $key;
    public int $flatId;

    public bool $delete;

    public function __construct(string $key, int $flatId, bool $delete)
    {
        parent::__construct('Добавить ключ (' . $key . ', ' . $flatId . ', ' . $delete . ')');

        $this->key = $key;
        $this->flatId = $flatId;

        $this->delete = $delete;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $flat = backend('households')->getFlat($this->flatId);

        if (!$flat)
            return false;

        $entrances = backend('households')->getEntrances('flatId', $this->flatId);

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function add(int $id, int $flat): void
    {
        $domophone = backend('households')->getDomophone($id);

        if (!$domophone)
            return;

        try {
            $device = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

            if ($this->delete)
                $device->removeRfid($this->key);
            else
                $device->addRfid($this->key, $flat);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);
        }
    }
}