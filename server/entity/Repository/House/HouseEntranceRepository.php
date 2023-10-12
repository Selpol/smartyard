<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method HouseEntrance fetchRaw(string $query, array $params = [])
 * @method HouseEntrance[] fetchAllRaw(string $query, array $params = [])
 * @method Page<HouseEntrance> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method HouseEntrance findById(int $id)
 *
 * @extends Repository<int, HouseEntrance>
 */
#[Singleton]
class HouseEntranceRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(HouseEntrance::class);
    }
}