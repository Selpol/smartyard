<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

use Psr\Log\LoggerAwareInterface;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Selpol\Service\DeviceService;
use Throwable;

#[Executable('cron:run', 'Запуск cron задач')]
class CronRunCommand implements LoggerAwareInterface
{
    use LoggerRunnerTrait;

    #[Execute]
    public function execute(CliIO $io, array $arguments): void
    {
        $parts = array_map(static fn(\UnitEnum $value) => $value->name, CronEnum::cases());
        $part = false;

        foreach ($parts as $p) {
            if (array_key_exists($p, $arguments)) {
                $part = $p;

                break;
            }
        }

        if ($part) {
            $part = CronEnum::from($part);

            $start = microtime(true) * 1000;
            $io->writeLine('Processing cron ' . $part->name);

            $values = [FrsFeature::class, FileFeature::class, DeviceService::class];

            foreach ($values as $value) {
                $instance = container($value);

                if (!($instance instanceof CronInterface)) {
                    $io->writeLine('Skipped ' . $value);

                    continue;
                }

                try {
                    if ($instance->cron($part)) {
                        $io->writeLine('Success processed feature ' . $value . ', part ' . $part->name);
                    } else {
                        $io->writeLine('Fail processed feature ' . $value . ', part ' . $part->name);
                    }
                } catch (Throwable $throwable) {
                    $io->writeLine('Error cron ' . $throwable);
                }
            }

            $elapsed = microtime(true) * 1000 - $start;

            $this->logger?->debug('Cron done ' . $elapsed);
            $io->writeLine('Cron done ' . $elapsed);
        } else {
            $io->writeLine('Cron skip');
        }
    }
}