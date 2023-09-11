<?php

namespace Selpol\Task\Tasks\Intercom;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DomophoneService;
use Selpol\Task\Task;
use Throwable;

class IntercomCodeTask extends Task
{
    public int $flatId;
    public string $code;

    public function __construct(int $flatId, string $code)
    {
        parent::__construct('Синхронизация кода (' . $flatId . ',' . $code . ')');

        $this->flatId = $flatId;
        $this->code = $code;
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
                    $this->code($id, $flat['flat']);
            }

            return true;
        }

        return false;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function code(int $id, int $flat): void
    {
        $domophone = backend('households')->getDomophone($id);

        if (!$domophone)
            return;

        try {
            $panel = container(DomophoneService::class)->get($domophone['model'], $domophone['url'], $domophone['credentials']);

            $panel->delete_open_code($flat);
            $panel->add_open_code($this->code, $flat);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);
        }
    }
}