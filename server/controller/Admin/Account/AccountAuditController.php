<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Account;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Account\AccountAuditIndexRequest;
use Selpol\Entity\Model\Audit;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Аудит пользователей
 */
#[Controller('/admin/account/audit')]
readonly class AccountAuditController extends AdminRbtController
{
    /**
     * Получить список аудита
     */
    #[Get]
    public function index(AccountAuditIndexRequest $request): ResponseInterface
    {
        return self::success(Audit::fetchPage(
            $request->page,
            $request->size,
            criteria()
                ->equal('user_id', $request->user_id)
                ->equal('auditable_id', $request->auditable_id)
                ->equal('auditable_type', $request->auditable_type)
                ->equal('event_ip', $request->event_ip)
                ->equal('event_type', $request->event_type)
                ->like('event_target', $request->event_target)
                ->equal('event_code', $request->event_code)
                ->like('event_message', $request->event_message)
                ->desc('created_at')
        ));
    }
}