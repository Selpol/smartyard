<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Frs;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Frs\FrsFace;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method FrsFace fetchRaw(string $query, array $params = [])
 * @method FrsFace[] fetchAllRaw(string $query, array $params = [])
 * @method Page<FrsFace> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method FrsFace findById(int $id)
 *
 * @extends Repository<int, FrsFace>
 */
#[Singleton]
class FrsFaceRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(FrsFace::class);
    }
}