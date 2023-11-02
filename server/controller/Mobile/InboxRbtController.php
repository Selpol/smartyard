<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;

#[Controller('/mobile/inbox')]
readonly class InboxRbtController extends RbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/read')]
    public function read(ServerRequestInterface $request): Response
    {
        $userId = $this->getUser()->getIdentifier();

        $validate = validator(['messageId' => $request->getQueryParams()['messageId']], ['messageId' => rule()->int()->clamp(0)]);

        container(InboxFeature::class)->markMessageAsRead($userId, $validate['messageId'] ?? false);

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