<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method HouseSubscriber fetchRaw(string $query, array $params = [])
 * @method HouseSubscriber[] fetchAllRaw(string $query, array $params = [])
 * @method Page<HouseSubscriber> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method HouseSubscriber findById(int $id)
 *
 * @extends Repository<int, HouseSubscriber>
 */
#[Singleton]
class HouseSubscriberRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(HouseSubscriber::class);
    }

    public function findByMobile(string $mobile): HouseSubscriber
    {
        return $this->fetchRaw('SELECT * FROM ' . $this->table . ' WHERE id = :id', [$this->id => $mobile]);
    }
}