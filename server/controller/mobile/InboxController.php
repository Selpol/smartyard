<?php

namespace Selpol\Controller\mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
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
        $user = $this->getSubscriber();

        $validate = validator(['messageId' => $this->request->getQueryParam('messageId')], ['messageId' => [Rule::int(), Rule::min(0), Rule::max()]]);

        backend("inbox")->markMessageAsReaded($user['subscriberId'], $validate['messageId'] ?? false);

        return $this->rbtResponse();
    }

    public function unread(): Response
    {
        $user = $this->getSubscriber();

        return $this->rbtResponse(data: ['count' => backend('inbox')->unreaded($user['subscriberId']), 'chat' => 0]);
    }
}