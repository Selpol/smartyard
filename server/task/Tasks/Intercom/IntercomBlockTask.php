<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Throwable;

class IntercomBlockTask extends Task
{
    public function __construct()
    {
        parent::__construct('Синхронизация блокировок домофона');
    }

    public function onTask(): bool
    {
        $blocks = FlatBlock::fetchAll(criteria()->equal('service', BlockFeature::SERVICE_INTERCOM));

        $this->setProgress(5.0);

        /** @var array<int, IntercomDevice|bool> $intercoms */
        $intercoms = [];

        $progress = 5.0;
        $delta = (100 - $progress) / count($blocks);

        foreach ($blocks as $block) {
            try {
                $flat = HouseFlat::findById($block->flat_id)->flat;
                $entrances = container(HouseFeature::class)->getEntrances('flatId', $block->flat_id);

                foreach ($entrances as $entrance) {
                    $intercomId = $entrance['domophoneId'];

                    if (!array_key_exists($intercomId, $intercoms)) {
                        $intercom = container(DeviceService::class)->intercomById($intercomId);

                        try {
                            if ($intercom->ping())
                                $intercoms[$intercomId] = $intercom;
                        } catch (Throwable) {
                            $intercoms[$intercomId] = false;
                        }
                    }

                    if ($intercoms[$intercomId] === false) {
                        continue;
                    }

                    if ($intercoms[$intercomId] instanceof ApartmentInterface) {
                        $intercoms[$intercomId]->setApartmentHandset(intval($flat), false);
                    }
                }

                $progress += $delta;

                $this->setProgress($progress);
            } catch (Throwable $throwable) {
                file_logger('intercom')->error($throwable);
            }
        }

        return true;
    }
}