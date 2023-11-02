<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;

class IntercomEntranceTask extends Task implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public int $entranceId;

    public function __construct(int $entranceId)
    {
        parent::__construct('Синхронизация входа (' . $entranceId . ')');

        $this->entranceId = $entranceId;
    }

    public function onTask(): bool
    {
        $entrance = container(HouseFeature::class)->getEntrance($this->entranceId);
        $flats = container(HouseFeature::class)->getFlats('entranceId', $entrance);

        try {
            $id = $entrance['domophoneId'];
            $device = intercom($id);

            $this->setProgress(25);

            if (!$device->ping())
                throw new DeviceException($device, 'Устройство не доступно');

            $this->setProgress(50);

            foreach ($flats as $flat) {
                $apartment = $flat['flat'];
                $apartment_levels = array_map('intval', explode(',', $entrance['cmsLevels']));

                $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($id) {
                    return $entrance['domophoneId'] == $id;
                });

                foreach ($flat_entrances as $flat_entrance) {
                    if (isset($flat_entrance['apartmentLevels']))
                        $apartment_levels = array_map('intval', explode(',', $flat_entrance['apartmentLevels']));

                    if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment)
                        $apartment = $flat_entrance['apartment'];
                }

                $device->setApartment(
                    $apartment,
                    $entrance['shared'] ? false : $flat['cmsEnabled'],
                    $entrance['shared'] ? [] : [sprintf('1%09d', $flat['flatId'])],
                    $apartment_levels,
                    intval($flat['openCode']) ?: 0
                );
            }
            return true;
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}