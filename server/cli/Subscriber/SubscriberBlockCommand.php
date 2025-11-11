<?php declare(strict_types=1);

namespace Selpol\Cli\Subscriber;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\RedisService;

#[Executable('subscriber:block', 'Заблокировать сессии абонента')]
class SubscriberBlockCommand
{
    #[Execute]
    public function execute(CliIO $io, RedisService $service): void
    {
        $ids = explode(',', $io->readLine('Сессии абонента> '));

        $usable = $service->jti();

        foreach ($ids as $id) {
            $usable->setEx($id, 68400 * 30, true);
        }
    }
}