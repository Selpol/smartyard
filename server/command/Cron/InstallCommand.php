<?php declare(strict_types=1);

namespace Selpol\Command\Cron;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('cron:install', 'Установить cron-задачи')]
class InstallCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(): void
    {
        $crontab = [];

        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $cli = PHP_BINARY . " " . __FILE__ . " cron:run";

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!")
                $skip = true;

            if (!$skip)
                $clean[] = $line;

            if ($line === "## RBT crons end, dont touch!!!")
                $skip = false;
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        $clean[] = "";

        $clean[] = "## RBT crons start, dont touch!!!";
        $lines++;
        $clean[] = "*/1 * * * * $cli=minutely";
        $lines++;
        $clean[] = "*/5 * * * * $cli=5min";
        $lines++;
        $clean[] = "1 */1 * * * $cli=hourly";
        $lines++;
        $clean[] = "1 1 */1 * * $cli=daily";
        $lines++;
        $clean[] = "1 1 1 */1 * $cli=monthly";
        $lines++;
        $clean[] = "## RBT crons end, dont touch!!!";
        $lines++;

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        $this->getLogger()->debug('Cron-задачи установлены', ['lines' => $lines]);
    }
}