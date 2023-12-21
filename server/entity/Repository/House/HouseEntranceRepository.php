<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method HouseEntrance|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method HouseEntrance[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<HouseEntrance> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method HouseEntrance|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, HouseEntrance>
 */
#[Singleton]
readonly class HouseEntranceRepository extends EntityRepository
{
    /**
     * @use AuditTrait<HouseEntrance>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(HouseEntrance::class);

        $this->auditName = 'Дом-Вход';
    }
}