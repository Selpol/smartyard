<?php declare(strict_types=1);

namespace Selpol\Cli\Schedule;

use Psr\Log\LoggerAwareInterface;
use Selpol\Entity\Model\Schedule;
use Selpol\Feature\Schedule\ScheduleFeature;
use Selpol\Feature\Schedule\ScheduleTime;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Selpol\Task\Task;
use Throwable;

#[Executable('schedule:run', 'Запуск задачи расписания')]
class ScheduleRunCommand implements LoggerAwareInterface
{
    use LoggerRunnerTrait;

    #[Execute]
    public function execute(CliIO $io, ScheduleFeature $feature): void
    {
        $this->setLogger(file_logger('schedule'));

        $start = microtime(true) * 1000;
        $io->writeLine('Processing schedule');

        $time = ScheduleTime::fromGlobal();

        $schedules = Schedule::fetchAll(criteria()->equal('status', 1));

        foreach ($schedules as $schedule) {
            if ($time->at($schedule->time)) {
                if ($schedule->task && class_exists($schedule->task)) {
                    $this->logger?->debug('Schedule run', ['id' => $schedule->id, 'schedule' => $schedule->title, 'task' => $schedule->task]);

                    try {
                        $class = $schedule->task;

                        $task = new $class($schedule->id, $time->getTime());

                        if ($task instanceof Task) {
                            task($task)->high()->async();
                        }
                    } catch (Throwable $throwable) {
                        $this->logger?->error($throwable, ['id' => $schedule->id, 'time' => $time->getTime()]);
                    }
                } else {
                    $this->logger?->debug('Schedule run', ['id' => $schedule->id, 'schedule' => $schedule->title]);

                    $feature->execute($schedule, $time);
                }
            }
        }

        $elapsed = microtime(true) * 1000 - $start;

        $this->logger?->debug('Schedule done ' . $elapsed . 'ms');
        $io->writeLine('Schedule done ' . $elapsed . 'ms');
    }
}