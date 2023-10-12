<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Audit;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method Audit fetchRaw(string $query, array $params = [])
 * @method Audit[] fetchAllRaw(string $query, array $params = [])
 * @method Page<Audit> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method Audit findById(int $id)
 *
 * @extends Repository<int, Audit>
 */
#[Singleton]
class AuditRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(Audit::class);
    }
}