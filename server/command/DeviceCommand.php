<?php declare(strict_types=1);

namespace Selpol\Command;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Device\Exception\DeviceException;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
use Throwable;

#[Executable('device', 'Управление устройством')]
class DeviceCommand
{
    private const ACTION = ['Синхронизация', 'Перезапустить', 'Сбросить'];

    use LoggerCommandTrait;

    #[Execute]
    public function execute(CliIO $io, int $id, ?int $action): void
    {
        $device = intercom($id);

        if (!$device)
            throw new KernelException('Устройство не найдено');

        if (!$device->ping())
            throw new DeviceException($device, 'Устройство не в сети');

        $io->writeLine('Домофон ' . $id . ', модель: ' . $device->model->model);
        $action = $action ?: $io->toggle(self::ACTION);

        switch ($action) {
            case 0:
                $this->sync($id, $io);
                break;
            case 1:
                $device->reboot();
                $this->getLogger()->debug('Устройство перезапущено', ['id' => $id]);
                break;
            case 2:
                $device->reset();
                $this->getLogger()->debug('Устройство сброшенно', ['id' => $id]);
                break;
        }
    }

    private function sync(int $id, CliIO $io): void
    {
        $bar = $io->getOutput()->getBar();

        $bar->show();

        try {
            task(new IntercomConfigureTask($id))->sync(static fn(int|float $progress) => $bar->set(intval($progress)));

            $this->getLogger()->debug('Синхонизация домофона прошла успешно', ['id' => $id]);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable, ['id' => $id]);
        } finally {
            $bar->hide();
        }
    }
}