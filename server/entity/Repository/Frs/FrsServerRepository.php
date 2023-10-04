<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Frs;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Repository;

/**
 * @method FrsServer fetch(string $query, array $params = [])
 * @method FrsServer[] fetchAll(string $query, array $params = [])
 * @method FrsServer[] fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method FrsServer findById(int $id)
 *
 * @extends Repository<int, FrsServer>
 */
class FrsServerRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(FrsServer::class);
    }
}