<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Inbox;

use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Task\Task;

class InboxSubscriberTask extends Task
{
    public int $subscriberId;

    public function __construct(int $subscriberId, public string $title, public string $message, public string $action)
    {
        parent::__construct('Отправка сообщений для абонента (' . $subscriberId . ')');

        $this->subscriberId = $subscriberId;

        $this->setLogger(file_logger('task-inbox'));
    }

    public function onTask(): bool
    {
        container(InboxFeature::class)->sendMessage($this->subscriberId, $this->title, $this->message, $this->action);

        return true;
    }
}