<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Http\Response;
use Selpol\Validator\Rule;

class InboxController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function read(): Response
    {
        $userId = $this->getUser()->getIdentifier();

        $validate = validator(['messageId' => $this->request->getQueryParam('messageId')], ['messageId' => [Rule::int(), Rule::min(0), Rule::max()]]);

        container(InboxFeature::class)->markMessageAsRead($userId, $validate['messageId'] ?? false);

        return $this->rbtResponse();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function unread(): Response
    {
        $userId = $this->getUser()->getIdentifier();

        return $this->rbtResponse(data: ['count' => container(InboxFeature::class)->unRead($userId), 'chat' => 0]);
    }
}