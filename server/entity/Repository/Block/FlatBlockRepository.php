<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Block;

use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method FlatBlock|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method FlatBlock[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<FlatBlock> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method FlatBlock|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, FlatBlock>
 */
#[Singleton]
readonly class FlatBlockRepository extends EntityRepository
{
    /**
     * @use AuditTrait<FlatBlock>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(FlatBlock::class);

        $this->auditName = 'Блокировка-Квартира';
    }

    /**
     * @param int $value
     * @param int|null $service
     * @param int|null $limit
     * @return FlatBlock[]
     */
    public function findByFlatId(int $value, ?int $service = null, ?int $limit = null): array
    {
        return $this->fetchAll(criteria()->equal('flat_id', $value)->equal('service', $service)->limit($limit));
    }
}