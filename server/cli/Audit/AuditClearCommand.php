<?php declare(strict_types=1);

namespace Selpol\Cli\Audit;

use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('audit:clear', 'Очиста данных аудита')]
class AuditClearCommand
{
    #[Execute]
    public function execute(CliIO $io, AuditFeature $feature): void
    {
        $feature->clear();
        $io->writeLine('Audit cleared');
    }
}