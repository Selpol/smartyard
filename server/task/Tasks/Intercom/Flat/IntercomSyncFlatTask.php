<?php

namespace Selpol\Task\Tasks\Intercom\Flat;

use RuntimeException;
use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Code\Code;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Task;
use Throwable;

class IntercomSyncFlatTask extends Task
{
    public int $userId;

    public int $flatId;
    public bool $add;

    public function __construct(int $userId, int $flatId, bool $add)
    {
        parent::__construct('Синхронизация квартиры (' . $flatId . ')');

        $this->userId = $userId;

        $this->flatId = $flatId;
        $this->add = $add;
    }

    public function onTask(): bool
    {
        $flat = container(HouseFeature::class)->getFlat($this->flatId);

        if (!$flat) {
            return false;
        }

        $entrances = container(HouseFeature::class)->getEntrances('flatId', $this->flatId);

        if ($entrances && count($entrances) > 0) {
            foreach ($entrances as $entrance) {
                $id = $entrance['domophoneId'];

                if ($id) {
                    $this->apartment($id, $flat, $entrance);
                }
            }

            return true;
        }

        return false;
    }

    private function apartment(int $id, array $flat, array $entrance): void
    {
        try {
            $device = intercom($id);

            if (!$device instanceof ApartmentInterface) {
                return;
            }

            if (!$device->ping()) {
                return;
            }

            if ($this->userId >= 0) {
                container(AuditFeature::class)->auditForUserId($this->userId, $flat['flatId'], HouseFlat::class, 'update', '[Дом квартира] Обновление блокировки квартиры кв ' . $flat['flat'] . ' (' . $flat['flatId'] . ')');
            }

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

            $blockCms = container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CMS]);
            $blockCall = container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]);

            $apartment = new Apartment(
                $apartment,
                !$entrance['shared'] && !$blockCms && $flat['cmsEnabled'],
                !$entrance['shared'] && !$blockCall,
                count($apartment_levels) > 0 ? $apartment_levels[0] : ($device->model->vendor === 'BEWARD' ? 330 : null),
                count($apartment_levels) > 1 ? $apartment_levels[1] : ($device->model->vendor === 'BEWARD' ? 530 : null),
                ($entrance['shared'] || $blockCall) ? [] : [sprintf('1%09d', $flat['flatId'])],
            );

            if ($this->add) {
                $device->addApartment($apartment);
            } else {
                $device->setApartment($apartment);
            }

            if ($device instanceof CodeInterface) {
                $device->addCode(new Code(intval($flat['openCode']) ?: 0, $apartment->apartment));
            }
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            if ($throwable instanceof KernelException) {
                throw $throwable;
            }

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}