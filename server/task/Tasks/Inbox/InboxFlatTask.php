<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Inbox;

use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Task\Task;

class InboxFlatTask extends Task
{
    public int $flatId;

    public string $title;
    public string $message;

    public string $action;

    public function __construct(int $flatId, string $title, string $message, string $action)
    {
        parent::__construct('Отправка сообщений для квартиры (' . $flatId . ')');

        $this->flatId = $flatId;

        $this->title = $title;
        $this->message = $message;
        $this->action = $action;
    }

    public function onTask(): bool
    {
        container(InboxFeature::class)->sendMessageToFlat($this->flatId, $this->title, $this->message, $this->action);

        return true;
    }
}