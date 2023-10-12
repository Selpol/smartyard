<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Dvr;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method DvrRecord fetchRaw(string $query, array $params = [])
 * @method DvrRecord[] fetchAllRaw(string $query, array $params = [])
 * @method Page<DvrRecord> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method DvrRecord findById(mixed $id)
 *
 * @extends Repository<int, DvrRecord>
 */
#[Singleton]
class DvrRecordRepository extends Repository
{
    protected function __construct()
    {
        parent::__construct(DvrRecord::class);
    }
}