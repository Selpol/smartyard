<?php declare(strict_types=1);

namespace Selpol\Feature\Group;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Feature;
use Selpol\Feature\Group\Internal\InternalGroupFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalGroupFeature::class)]
readonly abstract class GroupFeature extends Feature
{
    protected const DEFAULT_DATABASE = 'rbt';

    public const FOR_SUBSCRIBER = HouseSubscriber::class;
    public const FOR_CONTRACTOR = 'Contractor';

    public const FOR_MAP = [
        'subscriber' => self::FOR_SUBSCRIBER,
        'contractor' => self::FOR_CONTRACTOR
    ];

    public const TYPE_CAMERA = DeviceCamera::class;
    public const TYPE_INTERCOM = DeviceIntercom::class;
    public const TYPE_KEY = HouseKey::class;
    public const TYPE_ADDRESS = AddressHouse::class;

    public const TYPE_MAP = [
        'camera' => self::TYPE_CAMERA,
        'intercom' => self::TYPE_INTERCOM,
        'key' => self::TYPE_KEY,
        'address' => self::TYPE_ADDRESS,
    ];

    public abstract function find(?string $name, ?string $type, ?string $for, int $page, int $limit): GroupPage|bool;

    public abstract function findOne(string $name, string $type, string $for, mixed $id): array|bool;

    public abstract function insert(string $name, string $type, string $for, mixed $id, array $value): bool;

    public abstract function update(string $name, string $type, string $for, mixed $id, array $value): bool;

    public abstract function delete(string $name, string $type, string $for, mixed $id): bool;
}