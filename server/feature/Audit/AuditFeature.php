<?php declare(strict_types=1);

namespace Selpol\Feature\Audit;

use Selpol\Entity\Model\Audit;
use Selpol\Feature\Feature;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;

abstract class AuditFeature extends Feature
{
    /**
     * @param int $userId
     * @param string|null $auditableId
     * @param string|null $auditableType
     * @param string|null $eventIp
     * @param string|null $eventType
     * @param string|null $eventTarget
     * @param string|null $eventCode
     * @param string|null $eventMessage
     * @param int|null $page
     * @param int|null $size
     * @return array<Audit>|null
     */
    public abstract function audits(int $userId, ?string $auditableId, ?string $auditableType, ?string $eventIp, ?string $eventType, ?string $eventTarget, ?string $eventCode, ?string $eventMessage, ?int $page, ?int $size): ?array;

    public abstract function audit(ServerRequest $request, Response $response): void;

    public abstract function clear(): void;
}