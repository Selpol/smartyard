<?php

namespace Selpol\Task\Tasks\Intercom\Flat;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Selpol\Task\Task;
use Throwable;

class IntercomSyncFlatTask extends Task
{
    public int $flatId;

    public function __construct(int $flatId)
    {
        parent::__construct('Синхронизация квартиры (' . $flatId . ')');

        $this->flatId = $flatId;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $flat = backend('households')->getFlat($this->flatId);

        if (!$flat)
            return false;

        $entrances = backend('households')->getEntrances('flatId', $this->flatId);

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function apartment(int $id, array $flat, array $entrance): void
    {
        $domophone = backend('households')->getDomophone($id);

        if (!$domophone)
            return;

        try {
            $device = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

            if (!$device->ping())
                throw new RuntimeException('Устройство не доступно');

            $apartment = $flat['flat'];
            $apartment_levels = array_map('intval', explode(',', $entrance['cmsLevels']));

            $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($id) {
                return $entrance['domophoneId'] == $id;
            });

            foreach ($flat_entrances as $flat_entrance) {
                if (isset($flat_entrance['apartmentLevels'])) {
                    $apartment_levels = array_map('intval', explode(',', $flat_entrance['apartmentLevels']));
                }

                if ($flat_entrance['apartment'] != $apartment) {
                    $apartment = $flat_entrance['apartment'];
                }
            }

            $device->setApartment(
                $apartment,
                $entrance['shared'] ? false : $flat['cmsEnabled'],
                $entrance['shared'] ? [] : [sprintf('1%09d', $flat['flatId'])],
                $apartment_levels,
                $flat['openCode'] ?: 0
            );
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}