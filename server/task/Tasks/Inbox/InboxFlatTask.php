<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Inbox;

use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Task\Task;

class InboxFlatTask extends Task
{
    public int $flatId;

    public function __construct(int $flatId, public string $title, public string $message, public string $action)
    {
        parent::__construct('Отправка сообщений для квартиры (' . $flatId . ')');

        $this->flatId = $flatId;

        $this->setLogger(file_logger('task-inbox'));
    }

    public function onTask(): bool
    {
        container(InboxFeature::class)->sendMessageToFlat($this->flatId, $this->title, $this->message, $this->action);

        return true;
    }
}