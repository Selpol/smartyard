<?php declare(strict_types=1);

namespace Selpol\Cli\Inbox;

use Exception;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Task\Tasks\Inbox\InboxSubscriberTask;

#[Executable('inbox:server', 'Отправить сообщение о новом сервере клиенту')]
class InboxServerCommand
{
    /**
     * @throws Exception
     */
    #[Execute]
    public function execute(CliIO $io, string $value, int $subscriber): void
    {
        task(new InboxSubscriberTask($subscriber, 'Обновление сервера', $value, 'server'))->sync();
        $io->writeLine('Inbox server message send');
    }
}