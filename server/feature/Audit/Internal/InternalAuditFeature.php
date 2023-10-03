<?php declare(strict_types=1);

namespace Selpol\Feature\Audit\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Entity\Model\Audit;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Http\ServerRequest;
use Selpol\Service\AuthService;
use Selpol\Validator\Exception\ValidatorException;

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
            $query .= ' AND event_target LIKE :event_target';
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

        return Audit::fetchAll($query, $params);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function audit(string $auditableId, string $auditableType, string $eventType, string $eventMessage): void
    {
        if (!container(AuthService::class)->getUser()?->canScope())
            return;

        $user = container(AuthService::class)->getUser();

        $audit = new Audit();

        $audit->user_id = intval($user->getIdentifier());

        $audit->auditable_id = $auditableId;
        $audit->auditable_type = $auditableType;

        $audit->event_type = $eventType;
        $audit->event_message = $eventMessage;

        if (kernel()->getContainer()->has(ServerRequest::class)) {
            $request = container(ServerRequest::class);

            $audit->event_ip = connection_ip($request);
            $audit->event_target = $request->getRequestTarget();
        } else {
            $audit->event_ip = '0.0.0.0';
            $audit->event_target = '';
        }

        $audit->event_code = '';

        $audit->insert();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function clear(): void
    {
        $this->getDatabase()->modify('DELETE FROM audit');
    }
}