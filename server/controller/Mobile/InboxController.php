<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\Inbox\InboxIndexRequest;
use Selpol\Controller\Request\Mobile\Inbox\InboxReadRequest;
use Selpol\Entity\Model\Inbox\InboxMessage;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockMiddleware;

#[Controller('/mobile/inbox', includes: [BlockMiddleware::class => [BlockFeature::SUB_SERVICE_INBOX]])]
readonly class InboxController extends RbtController
{
    #[Get]
    public function index(InboxIndexRequest $request): ResponseInterface
    {
        $page = InboxMessage::fetchPage(
            $request->page,
            $request->size,
            criteria()
                ->equal('house_subscriber_id', $this->getUser()->getIdentifier())
                ->simple('date', '>=', $request->date)
                ->desc('date')
        );

        $result = [];

        foreach ($page->getData() as $message) {
            $result[] = $message->toArrayMap([
                'msg_id' => 'msg_id',

                'title' => 'title',
                'msg' => 'msg',

                'action' => 'action',

                'date' => 'date'
            ]);
        }

        return user_response(data: new EntityPage($result, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/read')]
    public function read(InboxReadRequest $request, InboxFeature $inboxFeature): ResponseInterface
    {
        $inboxFeature->markMessageAsRead($this->getUser()->getIdentifier(), $request->messageId ?? false);

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/unread')]
    public function unread(InboxFeature $inboxFeature): ResponseInterface
    {
        return user_response(data: ['count' => $inboxFeature->unRead($this->getUser()->getIdentifier()), 'chat' => 0]);
    }
}