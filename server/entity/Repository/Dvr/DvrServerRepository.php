<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Dvr;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Entity\Repository;

/**
 * @method DvrServer fetch(string $query, array $params = [])
 * @method DvrServer[] fetchAll(string $query, array $params = [])
 * @method DvrServer[] fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method DvrServer findById(mixed $id)
 *
 * @extends Repository<int, DvrServer>
 */
class DvrServerRepository extends Repository
{
    protected bool $audit = true;

    protected function __construct()
    {
        parent::__construct(DvrServer::class);
    }
}