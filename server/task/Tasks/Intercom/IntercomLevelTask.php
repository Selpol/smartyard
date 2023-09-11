<?php

namespace Selpol\Task\Tasks\Intercom;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DomophoneService;
use Selpol\Task\Task;
use Throwable;

class IntercomLevelTask extends Task
{
    public int $flatId;

    public int $apartment;

    public int $answer;
    public int $quiescent;

    public function __construct(int $flatId, int $apartment, int $answer, int $quiescent)
    {
        parent::__construct('Конфигурация Уровня (' . ', ' . $flatId . $apartment . ', ' . $answer . ', ' . $quiescent . ')');

        $this->flatId = $flatId;

        $this->apartment = $apartment;

        $this->answer = $answer;
        $this->quiescent = $quiescent;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $entrances = backend('households')->getEntrances('flatId', $this->flatId);

        if (!$entrances || count($entrances) == 0)
            return false;

        foreach ($entrances as $entrance) {
            if ($entrance['shared'])
                continue;

            $domophone = backend('households')->getDomophone($entrance['domophoneId']);

            if (!$domophone)
                continue;

            $this->level($domophone);
        }

        return true;
    }

    private function level(array $domophone): void
    {
        try {
            $panel = container(DomophoneService::class)->get($domophone['model'], $domophone['url'], $domophone['credentials']);

            $panel->configure_apartment_levels($this->apartment, $this->answer, $this->quiescent);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);
        }
    }
}