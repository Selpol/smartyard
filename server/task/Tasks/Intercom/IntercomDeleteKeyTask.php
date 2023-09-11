<?php

namespace Selpol\Task\Tasks\Intercom;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DomophoneService;
use Selpol\Task\Task;

class IntercomDeleteKeyTask extends Task
{
    public string $key;
    public int $flatId;

    public function __construct(string $key, int $flatId)
    {
        parent::__construct('Удалить ключ (' . $key . ')');

        $this->key = $key;
        $this->flatId = $flatId;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
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

                    $panel->clear_rfid($this->key);
                }
            }
        }

        return true;
    }
}