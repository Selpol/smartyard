<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Dvr;

use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method DvrServer fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method DvrServer[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<DvrServer> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method DvrServer|null findById(mixed $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, DvrServer>
 */
#[Singleton]
readonly class DvrServerRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(DvrServer::class);
    }
}