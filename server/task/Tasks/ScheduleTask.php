<?php declare(strict_types=1);

namespace Selpol\Task\Tasks;

use Selpol\Entity\Model\Schedule;
use Selpol\Feature\Schedule\Internal\Statement\StatementResult;
use Selpol\Feature\Schedule\ScheduleFeature;
use Selpol\Feature\Schedule\ScheduleTime;
use Selpol\Task\Task;
use Selpol\Task\TaskRetryInterface;
use Selpol\Task\Trait\TaskRetryTrait;
use Throwable;

class ScheduleTask extends Task implements TaskRetryInterface
{
    use TaskRetryTrait;

    public int $scheduleId;
    public int $time;

    public int $initialRetry = 12;

    public function __construct(int $scheduleId, int $time)
    {
        parent::__construct('Расписание (' . $scheduleId . ')');

        $this->scheduleId = $scheduleId;
        $this->time = $time;

        $this->setLogger(file_logger('task-schedule'));
    }

    public function onTask(): bool
    {
        $schedule = Schedule::findById($this->scheduleId, criteria()->equal('status', 1));

        if (!$schedule) {
            return false;
        }

        $feature = container(ScheduleFeature::class);
        $feature->check($schedule);

        $time = ScheduleTime::fromUnix($this->time);

        if (!$time->at($schedule->time)) {
            return false;
        }

        try {
            $result = $feature->execute($schedule, $time);

            if ($result == StatementResult::Success) {
                return true;
            }

            if ($result == StatementResult::Error) {
                $this->retry(15 * ($this->initialRetry - $this->retry));
            }
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable, ['id' => $schedule->id, 'time' => $time->getTime()]);

            $this->retry(15 * ($this->initialRetry - $this->retry));
        }

        return false;
    }
}
