<?php

namespace Selpol\Task\Tasks\Frs;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Task\Task;

class FrsRemoveStreamTask extends Task
{
    public string $url;
    public int $cameraId;

    public function __construct(string $url, int $cameraId)
    {
        parent::__construct('Удаление потока (' . $url . ', ' . $cameraId . ')');

        $this->url = $url;
        $this->cameraId = $cameraId;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        container(FrsFeature::class)->removeStream($this->url, $this->cameraId);

        return true;
    }
}