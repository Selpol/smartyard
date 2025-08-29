<?php declare(strict_types=1);

namespace Selpol\Cli\Plog;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Task\Tasks\Plog\PlogExportTask;

#[Executable('plog:export', 'Экспорт событий с квартиры')]
class PlogExportCommand
{
    #[Execute]
    public function execute(CliIO $io): void
    {
        $flatId = intval($io->readLine('Квартира> '));

        $bar = $io->getOutput()->getBar();

        $bar->show();

        $end = time();
        $start = $end - 86400 * 31;

        $task = new PlogExportTask($flatId, null, $start, $end);
        $task->setProgressCallback(static fn(int|float $value) => $bar->set(floatval($value)));

        task($task)->sync();

        $bar->hide();
    }
}