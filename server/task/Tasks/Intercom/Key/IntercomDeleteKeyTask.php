<?php

namespace Selpol\Task\Tasks\Intercom\Key;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
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
                    $this->delete($id);
            }

            return true;
        }

        return false;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function delete(int $id): void
    {
        $domophone = backend('households')->getDomophone($id);

        if (!$domophone)
            return;

        try {
            $device = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

            if (!$device->ping())
                throw new RuntimeException('Устройство не доступно');

            $device->removeRfid($this->key);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}