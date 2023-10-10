<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Frs;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method FrsServer fetchRaw(string $query, array $params = [])
 * @method FrsServer[] fetchAllRaw(string $query, array $params = [])
 * @method Page<FrsServer> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
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