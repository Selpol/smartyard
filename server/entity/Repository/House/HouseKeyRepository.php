<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Repository;

/**
 * @method HouseKey fetch(string $query, array $params = [])
 * @method HouseKey[] fetchAll(string $query, array $params = [])
 * @method HouseKey[] fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method HouseKey findById(int $id)
 *
 * @extends Repository<int, HouseKey>
 */
class HouseKeyRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(HouseKey::class);
    }
}