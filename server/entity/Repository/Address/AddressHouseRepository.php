<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method AddressHouse fetchRaw(string $query, array $params = [])
 * @method AddressHouse[] fetchAllRaw(string $query, array $params = [])
 * @method Page<AddressHouse> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method AddressHouse findById(int $id)
 *
 * @extends Repository<int, AddressHouse>
 */
class AddressHouseRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(AddressHouse::class);
    }
}