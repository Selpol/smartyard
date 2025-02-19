<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Entrance;

use Selpol\Entity\Model\Entrance\EntranceCms;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method EntranceCms|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntranceCms[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<EntranceCms> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method EntranceCms|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, EntranceCms>
 */
#[Singleton]
readonly class EntranceCmsRepository extends EntityRepository
{
    public function __construct()
    {
        parent::__construct(EntranceCms::class);
    }
}