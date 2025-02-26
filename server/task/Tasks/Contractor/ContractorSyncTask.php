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


    public function __construct(int $id, public bool $removeSubscriber, public bool $removeKey)
    {
        parent::__construct('Сихронизация подрядчика (' . $id . ')', $id);
    }

    public function onTask(): bool
    {
        $contractor = $this->getContractor();

        $addressesGroup = $this->getAddressesList();

        if ($addressesGroup === []) {
            return true;
        }

        $this->setProgress(5);

        $subscribersGroup = $this->getSubscribersList();
        $keysGroup = $this->getKeysList();

        if ($subscribersGroup === [] && $keysGroup === []) {
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

        if (count($addresses) > 0) {
            $delta = (50 - $progress) / count($addresses);

            foreach ($addresses as $address) {
                try {
                    $this->address($contractor, $address, $subscribers, $devices, $flats);

                    $progress += $delta;
                    $this->setProgress($progress);
                } catch (Throwable $throwable) {
                    $this->logger?->error($throwable);
                }
            }
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

        $intercoms = array_map(static fn(array $entrance): int => intval($entrance['domophoneId']), container(HouseFeature::class)->getEntrances('houseId', $address));

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
            $id = $subscriber[0];
            $role = $subscriber[1] == 1 || $subscriber[1] ? 0 : 1;

            try {
                if (array_key_exists($id, $subscribersInFlat)) {
                    if (HouseSubscriber::findById($id) instanceof HouseSubscriber && $subscribersInFlat[$id] !== $role) {
                        $houseFeature->updateSubscriberRoleInFlat($flat->house_flat_id, $id, $role);
                    }

                    unset($subscribersInFlat[$id]);

                    $this->logger?->debug('Обновлен пользователь', ['flat_id' => $flat->house_flat_id, 'subscriber' => $id, 'role' => $role]);
                } elseif ($houseFeature->addSubscriberToFlat($flat->house_flat_id, $id, $role)) {
                    $this->logger?->debug('Добавлен новый пользователь', ['flat_id' => $flat->house_flat_id, 'subscriber' => $id, 'role' => $role]);
                } else {
                    $this->logger?->debug('Не удалось добавить абонента', ['flat_id' => $flat->house_flat_id, 'subscriber' => $id, 'role' => $role]);
                }
            } catch (Throwable $throwable) {
                $this->logger?->error($throwable);
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
     * @param array<string, string[]> $keys
     * @return void
     */
    private function keys(Contractor $contractor, array $devices, array $flats, array $keys): void
    {
        /** @var array<IntercomDevice|KeyInterface> $intercoms */
        $intercoms = array_filter(array_map(static fn(int $id): ?IntercomDevice => intercom($id), array_keys($devices)), static fn(IntercomDevice $device): bool => $device instanceof KeyInterface && $device->pingRaw());

        $progress = 50;
        $delta = (100 - 50) / count($flats);

        foreach ($flats as $id => $flat) {
            try {
                /** @var array<string, HouseKey> $keysInFlat */
                $keysInFlat = array_reduce(HouseKey::fetchAll(criteria()->equal('access_type', 2)->equal('access_to', $id)), static function (array $previous, HouseKey $current) {
                    $previous[$current->rfid] = $current;

                    return $previous;
                }, []);

                $addKeys = [];

                foreach ($keys as $group => $groupKeys) {
                    foreach ($groupKeys as $key) {
                        if (array_key_exists($key, $keysInFlat)) {
                            $keysInFlat[$key]->comments = 'Ключ (' . $contractor->title . '-' . $group . ')';
                            $keysInFlat[$key]->update();

                            unset($keysInFlat[$key]);
                        } else {
                            try {
                                (new HouseKey([
                                    'rfid' => $key,

                                    'access_type' => 2,
                                    'access_to' => $id,

                                    'comments' => 'Ключ (' . $contractor->title . '-' . $group . ')'
                                ]))->insert();

                                $addKeys[] = $key;
                            } catch (Throwable $throwable) {
                                $this->logger?->error($throwable);
                            }
                        }
                    }
                }

                foreach ($intercoms as $intercom) {
                    try {
                        foreach ($addKeys as $key) {
                            $intercom->addKey(new Key($key, $flat));
                        }

                        if ($this->removeKey) {
                            foreach ($keysInFlat as $key => $value) {
                                $value->safeDelete();

                                $intercom->removeKey(new Key($key, $flat));
                            }
                        }
                    } catch (Throwable) {

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

    protected function setProgress(float|int $progress): void
    {
        $this->logger?->debug('Progress ' . $progress . ' for contractor id ' . $this->id);

        parent::setProgress($progress);
    }
}