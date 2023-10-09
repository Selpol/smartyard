<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Dvr;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method DvrRecord fetch(string $query, array $params = [])
 * @method DvrRecord[] fetchAll(string $query, array $params = [])
 * @method Page<DvrRecord> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method DvrRecord findById(mixed $id)
 *
 * @extends Repository<int, DvrRecord>
 */
class DvrRecordRepository extends Repository
{
    protected function __construct()
    {
        parent::__construct(DvrRecord::class);
    }
}