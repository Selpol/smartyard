<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method HouseKey fetchRaw(string $query, array $params = [])
 * @method HouseKey[] fetchAllRaw(string $query, array $params = [])
 * @method Page<HouseKey> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
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