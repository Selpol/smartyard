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
    /**
     * @throws NotFoundExceptionInterface
     */
    public function audits(int $userId, ?string $auditableId, ?string $auditableType, ?string $eventIp, ?string $eventType, ?string $eventTarget, ?string $eventCode, ?string $eventMessage, ?int $page, ?int $size): ?array
    {
        $query = 'SELECT * FROM audit WHERE user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($auditableId) {
            $query .= ' AND auditable_id = :auditable_id';
            $params['auditable_id'] = $auditableId;
        }

        if ($auditableType) {
            $query .= ' AND auditable_type = :auditable_type';
            $params['auditable_type'] = $auditableType;
        }

        if ($eventIp) {
            $query .= ' AND event_ip = :event_ip';
            $params['event_ip'] = $eventIp;
        }

        if ($eventType) {
            $query .= ' AND event_type = :event_type';
            $params['event_type'] = $eventType;
        }

        if ($eventTarget) {
            $query .= ' AND event_target = :event_target';
            $params['event_target'] = $eventTarget;
        }

        if ($eventCode) {
            $query .= ' AND event_code = :event_code';
            $params['event_code'] = $eventCode;
        }

        if ($eventMessage) {
            $query .= ' AND event_message LIKE :event_message';
            $params['event_message'] = $eventMessage;
        }

        $query .= ' ORDER BY created_at DESC';

        if ($page !== null && $size && $size > 0)
            $query .= ' LIMIT ' . $size . ' OFFSET ' . ($page * $size);

        return $this->getDatabase()->get(
            $query,
            $params,
            map: [
                'user_id' => 'userId',

                'auditable_id' => 'auditableId',
                'auditable_type' => 'auditableType',

                'event_ip' => 'eventIp',
                'event_type' => 'eventType',
                'event_target' => 'eventTarget',
                'event_code' => 'eventCode',
                'event_message' => 'eventMessage',

                'created_at' => 'createdAt',
                'updated_at' => 'updatedAt'
            ]
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function audit(ServerRequest $request, Response $response): ?int
    {
        if (config('audit', 0) == 0)
            return null;

        $user = container(AuthService::class)->getUser();

        if (!($user instanceof RedisAuthUser))
            return null;

        $audit = $request->getAttribute('audit', [
            'auditable_id' => '0',
            'auditable_type' => 'request',

            'event_type' => $request->getMethod(),
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

    /**
     * @throws NotFoundExceptionInterface
     */
    public function clear(): void
    {
        $this->getDatabase()->modify('DELETE FROM audit');
    }
}