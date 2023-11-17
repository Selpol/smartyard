<?php

namespace Selpol\Task\Tasks\Frs;

use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Task\Task;

class FrsRemoveStreamTask extends Task
{
    public int $frsServerId;
    public int $cameraId;

    public function __construct(int $frsServerId, int $cameraId)
    {
        parent::__construct('Удаление потока (' . $frsServerId . ', ' . $cameraId . ')');

        $this->frsServerId = $frsServerId;
        $this->cameraId = $cameraId;
    }

    public function onTask(): bool
    {
        $frsServer = FrsServer::findById($this->frsServerId, setting: setting()->columns(['url'])->nonNullable());

        container(FrsFeature::class)->removeStream($frsServer->url, $this->cameraId);

        return true;
    }
}