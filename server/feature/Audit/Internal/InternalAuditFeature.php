<?php declare(strict_types=1);

namespace Selpol\Feature\Audit\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Entity\Model\Audit;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Http\ServerRequest;
use Selpol\Service\AuthService;
use Selpol\Validator\Exception\ValidatorException;

readonly class InternalAuditFeature extends AuditFeature
{
    public function canAudit(): bool
    {
        return container(AuthService::class)->getUser()?->canScope() === true;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function audit(string $auditableId, string $auditableType, string $eventType, string $eventMessage): void
    {
        $user = container(AuthService::class)->getUserOrThrow();

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

    public function clear(): void
    {
        Audit::getRepository()->deleteSql();
    }
}