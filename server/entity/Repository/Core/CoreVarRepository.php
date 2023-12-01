<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Core;

use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method CoreVar|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method CoreVar[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<CoreVar> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method CoreVar|null findById(mixed $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, CoreVar>
 */
#[Singleton]
readonly class CoreVarRepository extends EntityRepository
{
    use AuditTrait;

    public string $auditName;

    public function __construct()
    {
        parent::__construct(CoreVar::class);

        $this->auditName = 'Ядро-Переменная';
    }

    public function findByName(string $name): ?CoreVar
    {
        return $this->fetch(criteria()->equal('var_name', $name));
    }
}