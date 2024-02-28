<?php declare(strict_types=1);

namespace Selpol\Task\Tasks;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Group\Group;
use Selpol\Feature\Group\GroupFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;
use Throwable;

class ContractTask extends Task
{
    public int $id;

    public function __construct(int $id)
    {
        parent::__construct('Сихронизация подрядчика (' . $id . ')');

        $this->id = $id;
    }

    public function onTask(): bool
    {
        try {
            $contractor = Contractor::findById($this->id, setting: setting()->nonNullable());

            /** @var Group<AddressHouse, Contractor, int>[] $addressesGroup */
            $addressesGroup = container(GroupFeature::class)->find(type: GroupFeature::TYPE_ADDRESS, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);

            if (count($addressesGroup) === 0)
                return true;

            /** @var Group<(HouseSubscriber | int)[], Contractor, int>[] $subscribersGroup */
            $subscribersGroup = container(GroupFeature::class)->find(type: GroupFeature::TYPE_SUBSCRIBER, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);

            /** @var Group<HouseKey, Contractor, int>[] $keysGroup */
            $keysGroup = container(GroupFeature::class)->find(type: GroupFeature::TYPE_KEY, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);

            if (count($subscribersGroup) === 0 && count($keysGroup) === 0)
                return true;

            /** @var int[] $addresses */
            $addresses = array_values(array_unique(array_reduce(array_map(static fn(Group $group) => $group->jsonSerialize(), $addressesGroup), static fn(array $previous, array $current) => array_merge($previous, (array)$current['value']), []), SORT_NUMERIC));

            /** @var int[][] $subscribers */
            $subscribers = array_reduce(array_map(static fn(Group $group) => $group->jsonSerialize(), $subscribersGroup), static fn(array $previous, array $current) => array_merge($previous, (array)$current['value']), []);

            /** @var string[] $keys */
            $keys = array_values(array_unique(array_reduce(array_map(static fn(Group $group) => $group->jsonSerialize(), $keysGroup), static fn(array $previous, array $current) => array_merge($previous, (array)$current['value']), [])));

            foreach ($addresses as $address)
                $this->address($contractor, $address, $subscribers, $keys);

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
     * @param string[] $keys
     * @return void
     */
    private function address(Contractor $contractor, int $address, array $subscribers, array $keys): void
    {
        $flat = $this->getFlat($contractor, $address);

        $this->subscriber($flat, $subscribers);
        $this->key($contractor, $flat, $address, $keys);
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
     * @param HouseFlat $flat
     * @param int $address
     * @param string[] $keys
     * @return void
     */
    private function key(Contractor $contractor, HouseFlat $flat, int $address, array $keys): void
    {
        $houseFeature = container(HouseFeature::class);

        /** @var IntercomDevice[] $devices */
        $devices = array_filter(
            array_map(static fn(array $entrance) => intercom($entrance['domophoneId']), $houseFeature->getEntrances('houseId', $address)),
            static fn(IntercomDevice $device) => $device->ping()
        );

        /** @var array<string, int> $keysInFlat */
        $keysInFlat = array_reduce($houseFeature->getKeys('flatId', $flat->house_flat_id), static function (array $previous, array $current) {
            $previous[$current['rfId']] = $current['keyId'];

            return $previous;
        }, []);

        foreach ($keys as $key) {
            if (array_key_exists($key, $keysInFlat)) unset($keysInFlat[$key]);
            else {
                (new HouseKey([
                    'rfid' => $key,

                    'access_type' => 2,
                    'access_to' => $flat->house_flat_id,

                    'comments' => 'Ключ (' . $contractor->title . ')'
                ]))->insert();

                foreach ($devices as $device)
                    $device->addRfidDeffer($key, intval($flat->flat));
            }
        }

        foreach ($keysInFlat as $key => $value) {
            $houseFeature->deleteKey($value);

            foreach ($devices as $device)
                $device->removeRfidDeffer($key, $flat->flat);
        }

        foreach ($devices as $device)
            $device->defferRfids();
    }

    private function getFlat(Contractor $contractor, int $address): HouseFlat
    {
        $flat = HouseFlat::fetch(criteria()->equal('address_house_id', $address)->equal('flat', $contractor->flat));

        if (!$flat) {
            $houseFeature = container(HouseFeature::class);

            $entrances = array_map(static fn(array $entrance) => $entrance['entranceId'], $houseFeature->getEntrances('houseId', $address));

            $flatId = $houseFeature->addFlat(
                $address,
                0,
                (string)$contractor->flat,
                '',
                $entrances,
                array_reduce($entrances, static function (array $previous, int $current) use ($contractor) {
                    $previous[$current] = [
                        'apartment' => (string)$contractor->flat,
                        'apartmentLevels' => ''
                    ];

                    return $previous;
                }, []),
                0,
                0,
                '',
                1,
                time(),
                0,
                0,
                ''
            );

            $flat = HouseFlat::findById($flatId, setting: setting()->nonNullable());
        }

        return $flat;
    }
}