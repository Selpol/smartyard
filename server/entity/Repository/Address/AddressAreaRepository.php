<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Address\AddressArea;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method AddressArea fetchRaw(string $query, array $params = [])
 * @method AddressArea[] fetchAllRaw(string $query, array $params = [])
 * @method Page<AddressArea> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method AddressArea findById(int $id)
 *
 * @extends Repository<int, AddressArea>
 */
#[Singleton]
class AddressAreaRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(AddressArea::class);
    }
}