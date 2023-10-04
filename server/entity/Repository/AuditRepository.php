<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Audit;
use Selpol\Entity\Repository;

/**
 * @method Audit fetch(string $query, array $params = [])
 * @method Audit[] fetchAll(string $query, array $params = [])
 *
 * @method Audit findById(int $id)
 *
 * @extends Repository<int, Audit>
 */
class AuditRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(Audit::class);
    }
}