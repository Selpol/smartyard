<?php declare(strict_types=1);

namespace Selpol\Feature\Audit\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Service\Auth\User\RedisAuthUser;
use Selpol\Service\AuthService;

class InternalAuditFeature extends AuditFeature
{
    public function audits(int $userId, ?string $auditableId, ?string $auditableType, ?string $eventIp, ?string $eventType, ?string $eventTarget, ?string $eventCode, ?string $eventMessage, ?int $page, ?int $size): ?array
    {
        return null;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function audit(ServerRequest $request, Response $response): ?int
    {
        $user = container(AuthService::class)->getUser();

        if (!($user instanceof RedisAuthUser))
            return null;

        $audit = $request->getAttribute('audit', [
            'auditable_id' => '0',
            'auditable_type' => 'request',

            'event_type' => 'request',
            'event_message' => 'Request'
        ]);

        $db = $this->getDatabase();

        $id = $db->get("SELECT NEXTVAL('audit_id_seq')", options: ['singlify'])['nextval'];

        $statement = $db->prepare('INSERT INTO audit(id, user_id, auditable_id, auditable_type, event_ip, event_type, event_target, event_code, event_message) VALUES (:id, :user_id, :auditable_id, :auditable_type, :event_ip, :event_type, :event_target, :event_code, :event_message)');

        return $statement->execute([
            'id' => $id,

            'user_id' => $user->getIdentifier(),

            'auditable_id' => $audit['auditable_id'],
            'auditable_type' => $audit['auditable_type'],

            'event_ip' => connection_ip($request),
            'event_type' => $audit['event_type'],
            'event_target' => $request->getRequestTarget(),
            'event_code' => $response->getStatusCode(),
            'event_message' => $audit['event_message']
        ]) ? $id : null;
    }
}