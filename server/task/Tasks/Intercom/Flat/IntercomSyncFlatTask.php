<?php

namespace Selpol\Task\Tasks\Intercom\Flat;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Task;
use Throwable;

class IntercomSyncFlatTask extends Task
{
    public int $flatId;
    public bool $add;

    public function __construct(int $flatId, bool $add)
    {
        parent::__construct('Синхронизация квартиры (' . $flatId . ')');

        $this->flatId = $flatId;
        $this->add = $add;
    }

    public function onTask(): bool
    {
        $flat = container(HouseFeature::class)->getFlat($this->flatId);

        if (!$flat)
            return false;

        $entrances = container(HouseFeature::class)->getEntrances('flatId', $this->flatId);

        if ($entrances && count($entrances) > 0) {
            foreach ($entrances as $entrance) {
                $id = $entrance['domophoneId'];

                if ($id)
                    $this->apartment($id, $flat, $entrance);
            }

            return true;
        }

        return false;
    }

    private function apartment(int $id, array $flat, array $entrance): void
    {
        try {
            $device = intercom($id);

            if (!$device->ping())
                return;

            $apartment = $flat['flat'];
            $apartment_levels = array_map('intval', explode(',', $entrance['cmsLevels']));

            $flat_entrances = array_filter($flat['entrances'], static fn($entrance) => $entrance['domophoneId'] == $id);

            foreach ($flat_entrances as $flat_entrance) {
                if (isset($flat_entrance['apartmentLevels'])) {
                    $apartment_levels = array_map('intval', explode(',', $flat_entrance['apartmentLevels']));
                }

                if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment) {
                    $apartment = $flat_entrance['apartment'];
                }
            }

            $block = container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CMS]);

            if ($this->add)
                $device->addApartment(
                    $apartment,
                    $entrance['shared'] ? false : ($block ? false : $flat['cmsEnabled']),
                    $entrance['shared'] ? [] : [sprintf('1%09d', $flat['flatId'])],
                    $apartment_levels,
                    intval($flat['openCode']) ?: 0
                );
            else
                $device->setApartment(
                    $apartment,
                    $entrance['shared'] ? false : ($block ? false : $flat['cmsEnabled']),
                    $entrance['shared'] ? [] : [sprintf('1%09d', $flat['flatId'])],
                    $apartment_levels,
                    intval($flat['openCode']) ?: 0
                );
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            if ($throwable instanceof KernelException)
                throw $throwable;

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}