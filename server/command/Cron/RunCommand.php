<?php declare(strict_types=1);

namespace Selpol\Command\Cron;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Throwable;

#[Executable('cron:run', 'Запустить cron-задачи')]
class RunCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(array $arguments): void
    {
        $parts = ["minutely", "5min", "hourly", "daily", "monthly"];
        $part = false;

        foreach ($parts as $p)
            if (in_array($p, $arguments)) {
                $part = $p;

                break;
            }

        if ($part) {
            $start = microtime(true) * 1000;
            $this->getLogger()->debug('Cron-задача запущена', ['part' => $part]);

            try {
                if (container(FrsFeature::class)->cron($part))
                    $this->getLogger()->debug('Cron-задача завершена', ['feature' => FrsFeature::class, 'part' => $part]);
                else
                    $this->getLogger()->error('Cron-задача ошибка', ['feature' => FrsFeature::class, 'part' => $part]);
            } catch (Throwable $throwable) {
                $this->getLogger()->error('Cron-задача ошибка' . PHP_EOL . $throwable, ['feature' => FrsFeature::class, 'part' => $part]);
            }

            $this->getLogger()->debug('Cron-задача завершено', ['ellapsed_ms' => microtime(true) * 1000 - $start]);
        }
    }
}