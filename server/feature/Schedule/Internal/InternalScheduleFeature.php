<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal;

use Selpol\Entity\Model\Schedule;
use Selpol\Feature\Schedule\Internal\Statement\StatementResult;
use Selpol\Feature\Schedule\Internal\Statement\TopStatement;
use Selpol\Feature\Schedule\ScheduleFeature;
use Selpol\Feature\Schedule\ScheduleTime;
use Selpol\Feature\Schedule\ScheduleTimeInterface;
use Selpol\Framework\Kernel\Exception\KernelException;

readonly class InternalScheduleFeature extends ScheduleFeature
{
    /**
     * @param Schedule $schedule
     * @return void
     * @throws KernelException
     */
    public function check(Schedule $schedule): void
    {
        ScheduleTime::check($schedule->time);

        $script = json_decode($schedule->script, true);

        if (!is_array($script)) {
            throw new KernelException('Не верный тип данных');
        }

        TopStatement::check($script);
    }

    public function execute(Schedule $schedule, ScheduleTimeInterface $time): StatementResult
    {
        $context = new Context($time);

        $statement = TopStatement::parse(json_decode($schedule->script, true));

        return $statement->execute($context);
    }
}
