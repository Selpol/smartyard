<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Frs;

use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method FrsServer|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method FrsServer[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<FrsServer> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method FrsServer|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, FrsServer>
 */
#[Singleton]
readonly class FrsServerRepository extends EntityRepository
{
    /**
     * @use AuditTrait<FrsServer>
     */
    use AuditTrait;

    public string $auditName;

    public function __construct()
    {
        parent::__construct(FrsServer::class);

        $this->auditName = 'Frs-Сервер';
    }
}