<?php declare(strict_types=1);

namespace Selpol\Feature\Block\Internal;

use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Feature\Block\BlockFeature;

readonly class InternalBlockFeature extends BlockFeature
{
    public function getBlocksForFlat(int $value, ?array $services): array
    {
        if ($services == null)
            return FlatBlock::getRepository()->findByFlatId($value);

        $blocks = [];

        foreach ($services as $service)
            $blocks = array_merge($blocks, FlatBlock::getRepository()->findByFlatId($value, intval($service)));

        return $blocks;
    }

    public function getBlocksForSubscriber(int $value, ?array $services): array
    {
        if ($services == null)
            return SubscriberBlock::getRepository()->findBySubscriberId($value);

        $blocks = [];

        foreach ($services as $service)
            $blocks = array_merge($blocks, SubscriberBlock::getRepository()->findBySubscriberId($value, intval($service)));

        return $blocks;
    }

    public function getFirstBlockForFlat(int $value, array $services): ?FlatBlock
    {
        foreach ($services as $service) {
            $blocks = FlatBlock::getRepository()->findByFlatId($value, $service, 1);

            if (count($blocks) > 0)
                return $blocks[0];
        }

        return null;
    }

    public function getFirstBlockForSubscriber(int $value, array $services): ?SubscriberBlock
    {
        foreach ($services as $service) {
            $blocks = SubscriberBlock::getRepository()->findBySubscriberId($value, $service, 1);

            if (count($blocks) > 0)
                return $blocks[0];
        }

        return null;
    }
}