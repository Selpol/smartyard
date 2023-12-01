<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\InboxReadRequest;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;

#[Controller('/mobile/inbox')]
readonly class InboxController extends RbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/read')]
    public function read(InboxReadRequest $request): Response
    {
        $userId = $this->getUser()->getIdentifier();

        container(InboxFeature::class)->markMessageAsRead($userId, $request->messageId ?? false);

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/unread')]
    public function unread(): Response
    {
        $userId = $this->getUser()->getIdentifier();

        return user_response(data: ['count' => container(InboxFeature::class)->unRead($userId), 'chat' => 0]);
    }
}