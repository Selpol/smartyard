<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Contractor;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Group\Group;
use Selpol\Feature\Group\GroupFeature;
use Selpol\Task\Task;

abstract class ContractorTask extends Task
{
    public int $id;

    public function __construct(string $title, int $id)
    {
        parent::__construct($title);

        $this->id = $id;
    }

    public function getContractor(): Contractor
    {
        return Contractor::findById($this->id, setting: setting()->nonNullable());
    }

    /**
     * @return Group<AddressHouse, Contractor, int>[]
     */
    public function getAddressesList(): array
    {
        return container(GroupFeature::class)->find(type: GroupFeature::TYPE_ADDRESS, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);
    }

    /**
     * @return Group<(HouseSubscriber | int)[], Contractor, int>[]
     */
    public function getSubscribersList(): array
    {
        return container(GroupFeature::class)->find(type: GroupFeature::TYPE_SUBSCRIBER, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);
    }

    /**
     * @return Group<HouseKey, Contractor, int>[]
     */
    public function getKeysList(): array
    {
        return container(GroupFeature::class)->find(type: GroupFeature::TYPE_KEY, for: GroupFeature::FOR_CONTRACTOR, id: $this->id);
    }

    /**
     * @param Group<AddressHouse, Contractor, int>[] $value
     * @return int[]
     */
    public function getUniqueAddressesIds(array $value): array
    {
        return array_values(array_unique(array_reduce(array_map(static fn(Group $group) => $group->jsonSerialize(), $value), static fn(array $previous, array $current) => array_merge($previous, (array)$current['value']), []), SORT_NUMERIC));
    }

    /**
     * @param Group<(HouseSubscriber | int)[], Contractor, int>[] $value
     * @return int[][]
     */
    public function getUniqueSubscribersIdsAndRoles(array $value): array
    {
        return array_reduce(array_map(static fn(Group $group) => $group->jsonSerialize(), $value), static fn(array $previous, array $current) => array_merge($previous, (array)$current['value']), []);
    }

    /**
     * @param Group<HouseKey, Contractor, int>[] $value
     * @return string[]
     */
    public function getUniqueKeys(array $value): array
    {
        return array_values(array_unique(array_reduce(array_map(static fn(Group $group) => $group->jsonSerialize(), $value), static fn(array $previous, array $current) => array_merge($previous, (array)$current['value']), [])));
    }
}