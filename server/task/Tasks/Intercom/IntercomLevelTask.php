<?php

namespace Selpol\Task\Tasks\Intercom;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DomophoneService;
use Selpol\Task\Task;
use Throwable;

class IntercomLevelTask extends Task
{
    public int $entranceId;

    public int $apartment;

    public int $answer;
    public int $quiescent;

    public function __construct(int $entranceId, int $apartment, int $answer, int $quiescent)
    {
        parent::__construct('Конфигурация Уровня (' . ', ' . $entranceId . $apartment . ', ' . $answer . ', ' . $quiescent . ')');

        $this->entranceId = $entranceId;

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
        $entrance = backend('households')->getEntrance($this->entranceId);

        if (!$entrance || $entrance['shared'])
            return false;

        $domophone = backend('households')->getDomophone($entrance['domophoneId']);

        if (!$domophone)
            return false;

        $this->level($domophone);

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