<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Flat;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Task;
use Throwable;

class IntercomCmsFlatTask extends Task
{
    public int $flatId;
    public bool $block;

    public function __construct(int $subscriberId, bool $block)
    {
        parent::__construct('Синхронизация КМС Трубки (' . $subscriberId . ', ' . $block . ')');

        $this->flatId = $subscriberId;
        $this->block = $block;
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
                throw new DeviceException($device, 'Устройство не доступно');

            $apartment = $flat['flat'];

            $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($id) {
                return $entrance['domophoneId'] == $id;
            });

            foreach ($flat_entrances as $flat_entrance) {
                if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment) {
                    $apartment = $flat_entrance['apartment'];
                }
            }

            $device->setApartmentCms(intval($apartment), !$entrance['shared'] && ((!$this->block && $flat['cmsEnabled'] == 1)));
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            if ($throwable instanceof KernelException)
                throw $throwable;

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}