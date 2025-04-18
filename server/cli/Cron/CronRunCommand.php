<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

use Psr\Log\LoggerAwareInterface;
use Selpol\Feature\Schedule\ScheduleTime;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Throwable;

#[Executable('cron:run', 'Запуск cron задач')]
class CronRunCommand implements LoggerAwareInterface
{
    use LoggerRunnerTrait;

    #[Execute]
    public function execute(CliIO $io): void
    {
        $start = microtime(true) * 1000;
        $io->writeLine('Processing cron ');

        $time = ScheduleTime::fromGlobal();

        $classes = kernel()->getContainer()->getTag(CronTag::CRON);

        foreach ($classes as $class) {
            $instance = container($class);

            if (!($instance instanceof CronInterface)) {
                $io->writeLine('Skipped ' . $class);

                continue;
            }

            try {
                if ($instance->cron($time)) {
                    $io->writeLine('Success processed feature ' . $class);
                } else {
                    $io->writeLine('Fail processed feature ' . $class);
                }
            } catch (Throwable $throwable) {
                $io->writeLine('Error cron ' . $throwable);
            }
        }

        $elapsed = microtime(true) * 1000 - $start;

        $this->logger?->debug('Cron done ' . $elapsed . 'ms');
        $io->writeLine('Cron done ' . $elapsed . 'ms');
    }
}