<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method HouseFlat fetchRaw(string $query, array $params = [])
 * @method HouseFlat[] fetchAllRaw(string $query, array $params = [])
 * @method Page<HouseFlat> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method HouseFlat findById(int $id)
 *
 * @extends Repository<int, HouseFlat>
 */
#[Singleton]
class HouseFlatRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(HouseFlat::class);
    }
}