<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Contractor;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Feature\House\HouseFeature;
use Throwable;

class ContractorSyncTask extends ContractorTask
{
    public function __construct(int $id)
    {
        parent::__construct('Сихронизация подрядчика (' . $id . ')', $id);
    }

    public function onTask(): bool
    {
        try {
            $contractor = $this->getContractor();

            $addressesGroup = $this->getAddressesList();

            if (count($addressesGroup) === 0)
                return true;

            $subscribersGroup = $this->getSubscribersList();
            $keysGroup = $this->getKeysList();

            if (count($subscribersGroup) === 0 && count($keysGroup) === 0)
                return true;

            $addresses = $this->getUniqueAddressesIds($addressesGroup);
            $subscribers = $this->getUniqueSubscribersIdsAndRoles($subscribersGroup);
            $keys = $this->getUniqueKeys($keysGroup);

            /** @var array<int, bool> $devices */
            $devices = [];

            /** @var array<int, int> $flats */
            $flats = [];

            foreach ($addresses as $address)
                try {
                    $this->address($contractor, $address, $subscribers, $devices, $flats);
                } catch (Throwable $throwable) {
                    file_logger('contract')->error($throwable);
                }

            try {
                $this->keys($contractor, $devices, $flats, $keys);
            } catch (Throwable $throwable) {
                file_logger('contract')->error($throwable);
            }

            return true;
        } catch (Throwable $throwable) {
            file_logger('contract')->error($throwable);

            return false;
        }
    }

    /**
     * @param Contractor $contractor
     * @param int $address
     * @param int[][] $subscribers
     * @param array<int, bool> $devices
     * @param array<int, int> $flats
     * @return void
     */
    private function address(Contractor $contractor, int $address, array $subscribers, array &$devices, array &$flats): void
    {
        $flat = $this->getOrCreateFlat($contractor, $address);

        $flats[$flat->house_flat_id] = intval($flat->flat);

        /** @var int[] $intercoms */
        $intercoms = array_map(static fn(array $entrance) => intval($entrance['domophoneId']), container(HouseFeature::class)->getEntrances('houseId', $address));

        foreach ($intercoms as $intercom)
            if (!array_key_exists($intercom, $devices))
                $devices[$intercom] = true;

        $this->subscriber($flat, $subscribers);
    }

    /**
     * @param HouseFlat $flat
     * @param int[][] $subscribers
     * @return void
     */
    private function subscriber(HouseFlat $flat, array $subscribers): void
    {
        $houseFeature = container(HouseFeature::class);

        /** @var array<int, int> $subscribersInFlat */
        $subscribersInFlat = array_reduce($houseFeature->getSubscribersInFlat($flat->house_flat_id), static function (array $previous, array $current) {
            $previous[$current['house_subscriber_id']] = $current['role'];

            return $previous;
        }, []);

        foreach ($subscribers as $subscriber) {
            if (array_key_exists($subscriber[0], $subscribersInFlat)) {
                if ($subscribersInFlat[$subscriber[0]] !== $subscriber[1])
                    $houseFeature->updateSubscriberRoleInFlat($flat->house_flat_id, $subscriber[0], $subscriber[1]);

                unset($subscribersInFlat[$subscriber[0]]);
            } else if ($houseFeature->addSubscriberToFlat($flat->house_flat_id, $subscriber[0], $subscriber[1]))
                file_logger('contract')->debug('Добавлен новый пользователь', ['flat_id' => $flat->house_flat_id, 'subscriber' => $subscriber[0], 'role' => $subscriber[1]]);
            else
                file_logger('contract')->debug('Не удалось добавить абонента', ['flat_id' => $flat->house_flat_id, 'subscriber' => $subscriber[0], 'role' => $subscriber[1]]);
        }

        foreach ($subscribersInFlat as $key => $_)
            $houseFeature->removeSubscriberFromFlat($flat->house_flat_id, $key);
    }

    /**
     * @param Contractor $contractor
     * @param array<int, bool> $devices
     * @param array<int, int> $flats
     * @param string[] $keys
     * @return void
     */
    private function keys(Contractor $contractor, array $devices, array $flats, array $keys): void
    {
        $houseFeature = container(HouseFeature::class);

        /** @var IntercomDevice[] $intercoms */
        $intercoms = array_filter(array_map(static fn(int $id) => intercom($id), array_keys($devices)), static fn(IntercomDevice $device) => $device->ping());

        foreach ($flats as $id => $flat) {
            /** @var array<string, int> $keysInFlat */
            $keysInFlat = array_reduce($houseFeature->getKeys('flatId', $id), static function (array $previous, array $current) {
                $previous[$current['rfId']] = $current['keyId'];

                return $previous;
            }, []);

            foreach ($keys as $key) {
                if (array_key_exists($key, $keysInFlat)) unset($keysInFlat[$key]);
                else {
                    (new HouseKey([
                        'rfid' => $key,

                        'access_type' => 2,
                        'access_to' => $id,

                        'comments' => 'Ключ (' . $contractor->title . ')'
                    ]))->insert();

                    foreach ($intercoms as $intercom)
                        $intercom->addRfidDeffer($key, $flat);
                }
            }

            foreach ($keysInFlat as $key => $value) {
                $houseFeature->deleteKey($value);

                foreach ($intercoms as $intercom)
                    $intercom->removeRfidDeffer($key, $flat);
            }
        }

        foreach ($intercoms as $intercom)
            $intercom->defferRfids();
    }
}