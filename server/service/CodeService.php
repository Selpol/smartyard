<?php

declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Cli\Cron\CronInterface;
use Selpol\Cli\Cron\CronTag;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Schedule\ScheduleTimeInterface;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

#[CronTag]
#[Singleton]
class CodeService implements CronInterface
{
    public function cron(ScheduleTimeInterface $value): bool
    {
        if (!$value->at('0 */2')) {
            return true;
        }

        /** @var array<Houseflat> $flats */
        $flats = HouseFlat::fetchAll(criteria()->simple('open_code_enabled', '<=', time() - 7 * 86400), setting: setting()->columns(['house_flat_id', 'open_code']));

        foreach ($flats as $flat) {
            $flat->open_code = '';
            $flat->update();

            task(new IntercomSyncFlatTask(-1, $flat->house_flat_id, false))->high()->async();
        }

        return true;
    }
}
