<?php declare(strict_types=1);

namespace Selpol\Feature\Group;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Contractor;
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

    public const FOR_CONTRACTOR = Contractor::class;

    public const FOR_MAP = [
        'contractor' => self::FOR_CONTRACTOR
    ];

    public const TYPE_SUBSCRIBER = HouseSubscriber::class;
    public const TYPE_CAMERA = DeviceCamera::class;
    public const TYPE_INTERCOM = DeviceIntercom::class;
    public const TYPE_KEY = HouseKey::class;
    public const TYPE_ADDRESS = AddressHouse::class;

    public const TYPE_MAP = [
        'subscriber' => self::TYPE_SUBSCRIBER,
        'camera' => self::TYPE_CAMERA,
        'intercom' => self::TYPE_INTERCOM,
        'key' => self::TYPE_KEY,
        'address' => self::TYPE_ADDRESS
    ];

    public abstract function fetchPage(?string $name, ?string $type, ?string $for, mixed $id, ?int $page, ?int $limit): array|bool;

    public abstract function findByAddress(int $id): array;

    public abstract function insert(string $name, string $type, string $for, mixed $id, array $value): bool;

    public abstract function get(string $name, string $type, string $for, mixed $id): array|bool;

    public abstract function update(string $name, string $type, string $for, mixed $id, array $value): bool;

    public abstract function delete(string $name, string $type, string $for, mixed $id): bool;
}