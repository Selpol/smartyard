<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Frs;

use Selpol\Entity\Model\Frs\FrsFace;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method FrsFace fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method FrsFace[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<FrsFace> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method FrsFace|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, FrsFace>
 */
#[Singleton]
readonly class FrsFaceRepository extends EntityRepository
{
    public function __construct()
    {
        parent::__construct(FrsFace::class);
    }
}