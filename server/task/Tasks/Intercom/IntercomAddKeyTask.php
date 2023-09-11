<?php

namespace Selpol\Task\Tasks\Intercom;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DomophoneService;
use Selpol\Task\Task;

class IntercomAddKeyTask extends Task
{
    public string $key;
    public int $flatId;

    public function __construct(string $key, int $flatId)
    {
        parent::__construct('Добавить ключ (' . $key . ')');

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
            $service = container(DomophoneService::class);

            foreach ($entrances as $entrance) {
                $id = $entrance['domophoneId'];

                if ($id) {
                    $domophone = backend('households')->getDomophone($id);

                    if (!$domophone)
                        continue;

                    try {
                        $panel = $service->get($domophone['model'], $domophone['url'], $domophone['credentials']);
                    } catch (Exception) {
                        continue;
                    }

                    $panel->add_rfid($this->key, $flat['flat']);
                }
            }

            return true;
        }

        return false;
    }
}