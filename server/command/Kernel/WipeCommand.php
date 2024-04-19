<?php declare(strict_types=1);

namespace Selpol\Command\Kernel;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Service\PrometheusService;

#[Executable('kernel:wipe', 'Удалить данные метрик')]
class WipeCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(PrometheusService $service): void
    {
        $service->wipe();
        $this->getLogger()->debug('Данные метрики удалены');
    }
}