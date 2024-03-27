<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Block;

use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method SubscriberBlock|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method SubscriberBlock[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<SubscriberBlock> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method SubscriberBlock|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, SubscriberBlock>
 */
#[Singleton]
readonly class SubscriberBlockRepository extends EntityRepository
{
    /**
     * @use AuditTrait<SubscriberBlock>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(SubscriberBlock::class);

        $this->auditName = 'Блокировка-Абонент';
    }

    /**
     * @param int $value
     * @param int|null $service
     * @param int|null $limit
     * @return SubscriberBlock[]
     */
    public function findBySubscriberId(int $value, ?int $service = null, ?int $limit = null): array
    {
        return $this->fetchAll(criteria()->equal('subscriber_id', $value)->equal('service', $service)->limit($limit));
    }
}