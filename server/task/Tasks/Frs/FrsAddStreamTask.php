<?php

namespace Selpol\Task\Tasks\Frs;

use Selpol\Feature\Frs\FrsFeature;
use Selpol\Task\Task;

class FrsAddStreamTask extends Task
{
    public string $url;
    public int $cameraId;

    public function __construct(string $url, int $cameraId)
    {
        parent::__construct('Добавление потока (' . $url . ', ' . $cameraId . ')');

        $this->url = $url;
        $this->cameraId = $cameraId;
    }

    public function onTask(): bool
    {
        container(FrsFeature::class)->addStream($this->url, $this->cameraId);

        return true;
    }
}