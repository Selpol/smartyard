<?php declare(strict_types=1);

namespace Selpol\Feature\Audit;

use Selpol\Feature\Feature;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;

abstract class AuditFeature extends Feature
{
    public abstract function audits(int $userId, ?string $auditableId, ?string $auditableType, ?string $eventIp, ?string $eventType, ?string $eventTarget, ?string $eventCode, ?string $eventMessage, ?int $page, ?int $size): ?array;

    public abstract function audit(ServerRequest $request, Response $response): ?int;
}