<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method Contractor|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method Contractor[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<Contractor> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method Contractor|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, Contractor>
 */
#[Singleton]
readonly class ContractorRepository extends EntityRepository
{
    /**
     * @use AuditTrait<Contractor>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(Contractor::class);

        $this->auditName = 'Подрядчики';
    }
}