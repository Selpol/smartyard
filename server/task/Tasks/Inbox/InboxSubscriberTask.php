<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Inbox;

use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Task\Task;

class InboxSubscriberTask extends Task
{
    public int $subscriberId;

    public string $title;
    public string $message;

    public string $action;

    public function __construct(int $subscriberId, string $title, string $message, string $action)
    {
        parent::__construct('Отправка сообщений для абонента (' . $subscriberId . ')');

        $this->subscriberId = $subscriberId;

        $this->title = $title;
        $this->message = $message;
        $this->action = $action;
    }

    public function onTask(): bool
    {
        container(InboxFeature::class)->sendMessage($this->subscriberId, $this->title, $this->message, $this->action);

        return true;
    }
}