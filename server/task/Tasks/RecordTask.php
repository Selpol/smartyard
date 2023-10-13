<?php

namespace Selpol\Task\Tasks;

use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Task\Task;
use Selpol\Task\TaskUnique;
use Selpol\Task\TaskUniqueInterface;
use Throwable;

class RecordTask extends Task implements TaskUniqueInterface
{
    public int $subscriberId;
    public int $recordId;

    public function __construct(int $subscriberId, int $recordId)
    {
        parent::__construct('Загрузка архива (' . $subscriberId . ', ' . $recordId . ')');

        $this->subscriberId = $subscriberId;
        $this->recordId = $recordId;
    }

    public function unique(): TaskUnique
    {
        return new TaskUnique([RecordTask::class, $this->recordId], 3600);
    }

    public function onTask(): bool
    {
        $uuid = container(ArchiveFeature::class)->runDownloadRecordTask($this->recordId);

        container(InboxFeature::class)->sendMessage(
            $this->subscriberId,
            'Видео готово к загрузке',
            'Внимание! Файлы на сервере будут доступны в течение 3 суток',
            config_get('api.mobile') . '/cctv/download/' . $uuid
        );

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        container(InboxFeature::class)->sendMessage(
            $this->subscriberId,
            'Видео',
            'К сожалению не удалось выгрузить ваше видео, обратитесь за помощью к технической поддержке',
            'icomtel://main/chat'
        );
    }
}