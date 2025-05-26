<?php declare(strict_types=1);

namespace Selpol\Cli\Frs;

use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\Schedule\AlwaysScheduleTime;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('frs:sync', 'Синхронизация данных FRS')]
class FrsSyncCommand
{
    #[Execute]
    public function execute(FrsFeature $feature): void
    {
        $feature->cron(new AlwaysScheduleTime());
    }
}