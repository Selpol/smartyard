<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Http\Exception\HttpException;
use Selpol\Task\Task;
use Throwable;

class IntercomEntranceTask extends Task
{
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

            if (!$device->ping())
                throw new HttpException(message: 'Устройство не доступно');

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
            if ($throwable instanceof HttpException)
                throw $throwable;

            logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}