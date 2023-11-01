<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Dvr;

use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method DvrRecord fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method DvrRecord[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<DvrRecord> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method DvrRecord|null findById(mixed $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, DvrRecord>
 */
#[Singleton]
readonly class DvrRecordRepository extends EntityRepository
{
    public function __construct()
    {
        parent::__construct(DvrRecord::class);
    }
}