<?php declare(strict_types=1);

namespace Selpol\Command\Inbox;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Task\Tasks\Inbox\InboxSubscriberTask;

#[Executable('inbox:server', 'Отправить новый сервер пользователю')]
class ServerCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(int $subscriber, string $value): void
    {
        task(new InboxSubscriberTask($subscriber, 'Обновление сервера', $value, 'server'))->sync();
        $this->getLogger()->debug('Сообщение отправлено абоненту');
    }
}