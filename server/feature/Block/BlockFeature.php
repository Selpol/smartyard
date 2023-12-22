<?php declare(strict_types=1);

namespace Selpol\Feature\Block;

use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Feature\Block\Internal\InternalBlockFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalBlockFeature::class)]
readonly abstract class BlockFeature extends Feature
{
    public const SERVICE_INTERCOM = 0;
    public const SERVICE_CCTV = 1;

    public const SUB_SERVICE_CALL = 2;
    public const SUB_SERVICE_OPEN = 3;
    public const SUB_SERVICE_EVENT = 4;
    public const SUB_SERVICE_ARCHIVE = 5;
    public const SUB_SERVICE_FRS = 6;
    public const SUB_SERVICE_CMS = 7;

    public const STATUS_ADMIN = 1;
    public const STATUS_BILLING = 2;

    /**
     * @param int $value
     * @param int[] $services
     * @return FlatBlock[]
     */
    public abstract function getBlocksForFlat(int $value, ?array $services): array;

    /**
     * @param int $value
     * @param int[] $services
     * @return SubscriberBlock[]
     */
    public abstract function getBlocksForSubscriber(int $value, ?array $services): array;

    public abstract function getFirstBlockForFlat(int $value, array $services): ?FlatBlock;

    public abstract function getFirstBlockForSubscriber(int $value, array $services): ?SubscriberBlock;
}