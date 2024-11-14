<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('cron:uninstall', 'Удалить cron задач')]
class CronUninstallCommand
{
    #[Execute]
    public function execute(CliIO $io): void
    {
        $crontab = [];

        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!") {
                $skip = true;
            }

            if (!$skip) {
                $clean[] = $line;
            } else {
                ++$lines;
            }

            if ($line === "## RBT crons end, dont touch!!!") {
                $skip = false;
            }
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        $io->writeLine('Uninstall crontabs ' . $lines);
    }
}