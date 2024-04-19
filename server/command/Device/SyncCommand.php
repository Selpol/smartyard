<?php declare(strict_types=1);

namespace Selpol\Command\Device;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
use Throwable;

#[Executable('device:sync', 'Синхронизация домофона')]
class SyncCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(int $id): void
    {
        try {
            $deviceIntercom = DeviceIntercom::findById($id, setting: setting()->columns(['house_domophone_id']));

            if ($deviceIntercom) {
                task(new IntercomConfigureTask($deviceIntercom->house_domophone_id))->sync();
                $this->getLogger()->debug('Синхонизация домофона прошла успешно', ['id' => $id]);
            } else $this->getLogger()->debug('Домофон не найден', ['id' => $id]);
        } catch (Throwable $throwable) {
            $this->getLogger()->debug('Ошибка синхронизации. ' . $throwable, ['id' => $id]);
        }
    }
}