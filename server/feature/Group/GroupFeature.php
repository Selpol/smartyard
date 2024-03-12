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
use Selpol\Framework\Entity\Entity;

#[Singleton(InternalGroupFeature::class)]
readonly abstract class GroupFeature extends Feature
{
    protected const DEFAULT_DATABASE = 'rbt';

    public const FOR_CONTRACTOR = Contractor::class;
    public const FOR_ADDRESS = AddressHouse::class;

    public const FOR_MAP = [
        'contractor' => self::FOR_CONTRACTOR,
        'address' => self::FOR_ADDRESS
    ];

    public const REVERSE_FOR_MAP = [
        self::FOR_CONTRACTOR => 'contractor',
        self::FOR_ADDRESS => 'address'
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

    public const REVERSE_TYPE_MAP = [
        self::TYPE_SUBSCRIBER => 'subscriber',
        self::TYPE_CAMERA => 'camera',
        self::TYPE_INTERCOM => 'intercom',
        self::TYPE_KEY => 'key',
        self::TYPE_ADDRESS => 'address'
    ];

    /**
     * @template V of Entity
     * @template F of Entity
     * @template T
     *
     * @param string|null $name
     * @param class-string<V>|null $type
     * @param class-string<F>|null $for
     * @param T $id
     * @param int|null $page
     * @param int|null $limit
     * @return Group<V, F, T>[]
     */
    public abstract function find(?string $name = null, ?string $type = null, ?string $for = null, mixed $id = null, ?int $page = null, ?int $limit = null): array;

    /**
     * @template V of Entity
     * @template F of Entity
     * @template T
     *
     * @param class-string<V> $type
     * @param class-string<F> $for
     * @param T $id
     * @param V $value
     * @return Group<V, F, T>[]
     */
    public abstract function findIn(string $type, string $for, mixed $id, mixed $value): array;

    public abstract function insert(string $name, string $type, string $for, mixed $id, array $value): string|bool;

    /**
     * @template V of Entity
     * @template F of Entity
     * @template T
     *
     * @param string $oid
     * @return Group<V, F, T>|bool
     */
    public abstract function get(string $oid): Group|bool;

    public abstract function update(string $oid, string $name, string $type, string $for, mixed $id, array $value): bool;

    public abstract function delete(string $oid): bool;

    public abstract function deleteFor(string $for, mixed $id): bool;
}