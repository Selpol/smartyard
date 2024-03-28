<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Server;

use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method StreamerServer|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method StreamerServer[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<StreamerServer> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method StreamerServer|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, StreamerServer>
 */
#[Singleton]
readonly class StreamerServerRepository extends EntityRepository
{
    /**
     * @use AuditTrait<StreamerServer>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(StreamerServer::class);

        $this->auditName = 'Стример';
    }
}