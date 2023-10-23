<?php

namespace Selpol\Entity\Repository\Sip;

use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method SipServer fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method SipServer[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<SipServer> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method SipServer findById(mixed $id)
 *
 * @extends EntityRepository<int, SipServer>
 */
#[Singleton]
readonly class SipServerRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(SipServer::class);
    }
}