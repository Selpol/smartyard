<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Contractor;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;

class ContractorSyncTask extends ContractorTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public $taskUniqueTtl = 600;

    public bool $removeSubscriber;
    public bool $removeKey;

    public function __construct(int $id, bool $removeSubscriber, bool $removeKey)
    {
        parent::__construct('Сихронизация подрядчика (' . $id . ')', $id);

        $this->removeSubscriber = $removeSubscriber;
        $this->removeKey = $removeKey;
    }

    public function onTask(): bool
    {
        $contractor = $this->getContractor();

        $addressesGroup = $this->getAddressesList();

        if (count($addressesGroup) === 0) {
            return true;
        }

        $this->setProgress(5);

        $subscribersGroup = $this->getSubscribersList();
        $keysGroup = $this->getKeysList();

        if (count($subscribersGroup) === 0 && count($keysGroup) === 0) {
            return true;
        }

        $this->setProgress(10);

        $addresses = $this->getUniqueAddressesIds($addressesGroup);
        $subscribers = $this->getUniqueSubscribersIdsAndRoles($subscribersGroup);
        $keys = $this->getUniqueKeys($keysGroup);

        $this->setProgress(15);

        /** @var array<int, bool> $devices */
        $devices = [];

        /** @var array<int, int> $flats */
        $flats = [];

        $progress = 15;
        $delta = (50 - $progress) / count($addresses);

        foreach ($addresses as $address)
            try {
                $this->address($contractor, $address, $subscribers, $devices, $flats);

                $progress += $delta;
                $this->setProgress($progress);
            } catch (Throwable $throwable) {
                $this->logger?->error($throwable);
            }

        $this->setProgress(50);

        try {
            $this->keys($contractor, $devices, $flats, $keys);
        } catch (Throwable $throwable) {
            $this->logger?->error($throwable);
        }

        return true;
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

        if ($flat == null) {
            return;
        }

        $flats[$flat->house_flat_id] = intval($flat->flat);

        /** @var int[] $intercoms */
        $intercoms = array_map(static fn(array $entrance) => intval($entrance['domophoneId']), container(HouseFeature::class)->getEntrances('houseId', $address));

        foreach ($intercoms as $intercom) {
            if (!array_key_exists($intercom, $devices)) {
                $devices[$intercom] = true;
            }
        }

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
                if (HouseSubscriber::findById($subscriber[0]) !== null) {
                    if ($subscribersInFlat[$subscriber[0]] !== $subscriber[1]) {
                        $houseFeature->updateSubscriberRoleInFlat($flat->house_flat_id, $subscriber[0], $subscriber[1]);
                    }
                }

                unset($subscribersInFlat[$subscriber[0]]);
            } else if ($houseFeature->addSubscriberToFlat($flat->house_flat_id, $subscriber[0], $subscriber[1])) {
                $this->logger?->debug('Добавлен новый пользователь', ['flat_id' => $flat->house_flat_id, 'subscriber' => $subscriber[0], 'role' => $subscriber[1]]);
            } else {
                $this->logger?->debug('Не удалось добавить абонента', ['flat_id' => $flat->house_flat_id, 'subscriber' => $subscriber[0], 'role' => $subscriber[1]]);
            }
        }

        if ($this->removeSubscriber) {
            foreach ($subscribersInFlat as $key => $_) {
                $houseFeature->removeSubscriberFromFlat($flat->house_flat_id, $key);
            }
        }
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

        $progress = 50;
        $delta = (100 - 50) / count($flats);

        foreach ($flats as $id => $flat) {
            try {
                /** @var array<string, int> $keysInFlat */
                $keysInFlat = array_reduce($houseFeature->getKeys('flatId', $id), static function (array $previous, array $current) {
                    $previous[$current['rfId']] = $current['keyId'];

                    return $previous;
                }, []);

                $addKeys = [];

                foreach ($keys as $key) {
                    if (array_key_exists($key, $keysInFlat)) {
                        unset($keysInFlat[$key]);
                    } else {
                        try {
                            (new HouseKey([
                                'rfid' => $key,

                                'access_type' => 2,
                                'access_to' => $id,

                                'comments' => 'Ключ (' . $contractor->title . ')'
                            ]))->insert();

                            $addKeys[] = $key;
                        } catch (Throwable $throwable) {
                            $this->logger?->error($throwable);
                        }
                    }
                }

                foreach ($intercoms as $intercom) {
                    if ($intercom instanceof KeyInterface) {
                        foreach ($addKeys as $key) {
                            $intercom->addKey(new Key($key, $flat));
                        }

                        if ($this->removeKey) {
                            foreach ($keysInFlat as $key => $value) {
                                $houseFeature->deleteKey($value);
                                $intercom->removeKey(new Key($key, $flat));
                            }
                        }
                    }
                }
            } catch (Throwable $throwable) {
                $this->logger?->error($throwable);
            } finally {
                $progress += $delta;
                $this->setProgress($progress);
            }
        }
    }
}