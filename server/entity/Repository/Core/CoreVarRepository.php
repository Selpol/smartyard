<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Core;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method CoreVar fetchRaw(string $query, array $params = [])
 * @method CoreVar[] fetchAllRaw(string $query, array $params = [])
 * @method Page<CoreVar> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method CoreVar findById(mixed $id)
 *
 * @extends Repository<int, CoreVar>
 */
class CoreVarRepository extends Repository
{
    protected function __construct()
    {
        parent::__construct(CoreVar::class);
    }
}