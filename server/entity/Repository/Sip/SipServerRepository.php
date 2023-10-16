<?php

namespace Selpol\Entity\Repository\Sip;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method SipServer fetchRaw(string $query, array $params = [])
 * @method SipServer[] fetchAllRaw(string $query, array $params = [])
 * @method Page<SipServer> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method SipServer findById(mixed $id)
 *
 * @extends Repository<int, SipServer>
 */
#[Singleton]
class SipServerRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(SipServer::class);
    }
}