<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Code\Code;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
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

        $this->setLogger(file_logger('task-intercom'));
    }

    public function onTask(): bool
    {
        $entrance = container(HouseFeature::class)->getEntrance($this->entranceId);
        $flats = container(HouseFeature::class)->getFlats('entranceId', $entrance);

        try {
            $id = $entrance['domophoneId'];
            $device = intercom($id);

            $this->setProgress(25);

            if (!$device->ping()) {
                throw new DeviceException($device, 'Устройство не доступно');
            }

            if (!$device instanceof ApartmentInterface) {
                return false;
            }

            $this->setProgress(50);

            foreach ($flats as $flat) {
                $apartment = $flat['flat'];

                $apartment_levels = [];

                $flat_entrances = array_filter($flat['entrances'], fn(array $entrance): bool => $entrance['domophoneId'] == $id);

                foreach ($flat_entrances as $flat_entrance) {
                    if (isset($flat_entrance['apartmentLevels'])) {
                        $apartment_levels = array_map('intval', explode(',', (string)$flat_entrance['apartmentLevels']));
                    }

                    if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment) {
                        $apartment = $flat_entrance['apartment'];
                    }
                }

                $device->setApartment(new Apartment(
                    $apartment,
                    $entrance['shared'] ? false : $flat['cmsEnabled'],
                    $entrance['shared'] ? false : $flat['cmsEnabled'],
                    array_key_exists(0, $apartment_levels) ? $apartment_levels[0] : ($device->model->vendor === 'BEWARD' ? 330 : ($device->model->vendor === 'IS' ? 255 : null)),
                    array_key_exists(1, $apartment_levels) ? $apartment_levels[1] : ($device->model->vendor === 'BEWARD' ? 530 : ($device->model->vendor === 'IS' ? 255 : null)),
                    $entrance['shared'] ? [] : [sprintf('1%09d', $flat['flatId'])],
                ));

                if ($device instanceof CodeInterface) {
                    $device->addCode(new Code(intval($flat['openCode']), $apartment->apartment));
                }
            }

            return true;
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);

            if ($throwable instanceof KernelException) {
                throw $throwable;
            }

            throw new RuntimeException($throwable->getMessage(), $throwable->getCode(), previous: $throwable);
        }
    }
}