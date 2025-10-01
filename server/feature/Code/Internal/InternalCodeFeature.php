<?php

declare(strict_types=1);

namespace Selpol\Feature\Code\Internal;

use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Code\CodeFeature;
use Selpol\Feature\Schedule\ScheduleTimeInterface;
use Selpol\Task\Tasks\Flat\FlatCodeDeleteTask;

readonly class InternalCodeFeature extends CodeFeature
{
    public function cron(ScheduleTimeInterface $value): bool
    {
        if (!$value->at('0 */2')) {
            return true;
        }

        /** @var array<Houseflat> $flats */
        $flats = HouseFlat::fetchAll(criteria()->simple('open_code_enabled', 'IS NOT', 'NULL')->simple('open_code_enabled', '<=', time() - 7 * 86400), setting: setting()->columns(['house_flat_id', 'open_code', 'open_code_enabled']));

        foreach ($flats as $flat) {
            task(new FlatCodeDeleteTask($flat->house_flat_id))->low()->async();
        }

        return true;
    }
}
