<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Contractor;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Group\Group;
use Selpol\Feature\Group\GroupFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;

abstract class ContractorTask extends Task
{
    public function __construct(string $title, public int $id)
    {
        parent::__construct($title);

        $this->setLogger(file_logger('task-contractor'));
    }

    protected function getContractor(): Contractor
    {
        return Contractor::findById($this->id, setting: setting()->nonNullable());
    }

    /**
     * @return Group<AddressHouse, Contractor, int>[]
     */
    protected function getAddressesList(): array
    {
        return container(GroupFeature::class)->find(type: GroupFeature::TYPE_ADDRESS, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);
    }

    /**
     * @return Group<(HouseSubscriber | int)[], Contractor, int>[]
     */
    protected function getSubscribersList(): array
    {
        return container(GroupFeature::class)->find(type: GroupFeature::TYPE_SUBSCRIBER, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);
    }

    /**
     * @return Group<HouseKey, Contractor, int>[]
     */
    protected function getKeysList(): array
    {
        return container(GroupFeature::class)->find(type: GroupFeature::TYPE_KEY, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);
    }

    /**
     * @param Group<AddressHouse, Contractor, int>[] $value
     * @return int[]
     */
    protected function getUniqueAddressesIds(array $value): array
    {
        return array_values(array_unique(array_reduce(array_map(static fn(Group $group): array => $group->jsonSerialize(), $value), static fn(array $previous, array $current): array => array_merge($previous, (array) $current['value']), []), SORT_NUMERIC));
    }

    /**
     * @param Group<(HouseSubscriber | int)[], Contractor, int>[] $value
     * @return int[][]
     */
    protected function getUniqueSubscribersIdsAndRoles(array $value): array
    {
        return array_reduce(array_map(static fn(Group $group): array => $group->jsonSerialize(), $value), static fn(array $previous, array $current): array => array_merge($previous, (array) $current['value']), []);
    }

    /**
     * @param Group<HouseKey, Contractor, int>[] $value
     * @return array<string, string[]>
     */
    protected function getUniqueKeys(array $value): array
    {
        return array_reduce(
            $value,
            static function (array $previous, Group $current): array {
                $previous[$current->name] = $current->value;

                return $previous;
            },
            []
        );
    }

    protected function getOrCreateFlat(Contractor $contractor, int $address): HouseFlat
    {
        $flat = HouseFlat::fetch(criteria()->equal('address_house_id', $address)->equal('flat', $contractor->flat), setting: setting()->columns(['house_flat_id', 'flat']));

        if (!$flat instanceof HouseFlat) {
            $houseFeature = container(HouseFeature::class);

            $entrances = array_map(static fn(array $entrance) => $entrance['entranceId'], $houseFeature->getEntrances('houseId', $address));

            $flatId = $houseFeature->addFlat(
                $address,
                0,
                (string) $contractor->flat,
                '',
                $entrances,
                array_reduce($entrances, static function (array $previous, int $current) use ($contractor) {
                    $previous[$current] = [
                        'apartment' => (string) $contractor->flat,
                        'apartmentLevels' => ''
                    ];

                    return $previous;
                }, []),
                null,
                0,
                time(),
                0,
                0,
                null,
                $contractor->title
            );

            $flat = HouseFlat::findById($flatId, setting: setting()->nonNullable()->columns(['house_flat_id', 'flat']));
        }

        return $flat;
    }
}