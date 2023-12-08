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
    public function read(InboxReadRequest $request, InboxFeature $inboxFeature): Response
    {
        $inboxFeature->markMessageAsRead($this->getUser()->getIdentifier(), $request->messageId ?? false);

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/unread')]
    public function unread(InboxFeature $inboxFeature): Response
    {
        return user_response(data: ['count' => $inboxFeature->unRead($this->getUser()->getIdentifier()), 'chat' => 0]);
    }
}