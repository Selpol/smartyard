<?php declare(strict_types=1);

namespace Selpol\Feature\Audit;

use Selpol\Feature\Audit\Internal\InternalAuditFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalAuditFeature::class)]
readonly abstract class AuditFeature extends Feature
{
    public abstract function canAudit(): bool;

    public abstract function audit(string $auditableId, string $auditableType, string $eventType, string $eventMessage): void;

    public abstract function auditForUserId(int $userId, string $auditableId, string $auditableType, string $eventType, string $eventMessage): void;

    public abstract function clear(): void;
}