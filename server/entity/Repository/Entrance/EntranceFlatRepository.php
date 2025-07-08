<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Entrance;

use Selpol\Entity\Model\Entrance\EntranceFlat;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method EntranceFlat|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntranceFlat[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<EntranceFlat> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method EntranceFlat|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, EntranceFlat>
 */
#[Singleton]
readonly class EntranceFlatRepository extends EntityRepository
{
    public function __construct()
    {
        parent::__construct(EntranceFlat::class);
    }
}