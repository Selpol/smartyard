<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Block;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Block\BlockDeleteRequest;
use Selpol\Controller\Request\Admin\Block\BlockSubscriberStoreRequest;
use Selpol\Controller\Request\Admin\Block\BlockUpdateRequest;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Inbox\InboxSubscriberTask;

/**
 * Блокировки абонентов
 */
#[Controller('/admin/block/subscriber')]
readonly class BlockSubscriberController extends AdminRbtController
{
    /**
     * Получить список блокировок абонента
     *
     * @param int $id Идентификатор абонента
     */
    #[Get('/{id}')]
    public function index(int $id): ResponseInterface
    {
        return self::success(SubscriberBlock::getRepository()->findBySubscriberId($id));
    }

    /**
     * Добавить блокировку
     */
    #[Post]
    public function store(BlockSubscriberStoreRequest $request): ResponseInterface
    {
        $subscriberBlock = new SubscriberBlock();

        $subscriberBlock->subscriber_id = $request->subscriber_id;

        $subscriberBlock->service = $request->service;
        $subscriberBlock->status = BlockFeature::STATUS_ADMIN;

        $subscriberBlock->cause = $request->cause;
        $subscriberBlock->comment = $request->comment;

        if ($subscriberBlock->safeInsert()) {
            if ($request->notify) {
                self::notify($subscriberBlock, true);
            }

            return self::success($subscriberBlock->id);
        }

        return self::error('Не удалось создать блокировку абонента', 400);
    }

    /**
     * Обновить блокировку
     */
    #[Put('/{id}')]
    public function update(BlockUpdateRequest $request): ResponseInterface
    {
        $subscriberBlock = SubscriberBlock::findById($request->id, setting: setting()->nonNullable());

        $subscriberBlock->cause = $request->cause;
        $subscriberBlock->comment = $request->comment;

        if ($subscriberBlock->safeUpdate()) {
            if ($request->notify) {
                self::notify($subscriberBlock, true);
            }

            return self::success($subscriberBlock->id);
        }

        return self::error('Не удалось обновить блокировку абонента', 400);
    }

    /**
     * Удалить блокировку
     */
    #[Delete('/{id}')]
    public function delete(BlockDeleteRequest $request): ResponseInterface
    {
        $subscriberBlock = SubscriberBlock::findById($request->id, setting: setting()->nonNullable());

        if ($subscriberBlock->status == BlockFeature::STATUS_BILLING && !container(AuthService::class)->checkScope('block-subscriber-billing-delete')) {
            return self::error('Не удалось удалить блокировку абонента', 400);
        }

        if ($subscriberBlock->safeDelete()) {
            if ($request->notify) {
                self::notify($subscriberBlock, false);
            }

            return self::success();
        }

        return self::error('Не удалось удалить блокировку абонента', 400);
    }

    private static function notify(SubscriberBlock $block, bool $status): void
    {
        $translate = BlockController::translate($block->service);

        if ($translate) {
            task(new InboxSubscriberTask(
                $block->subscriber_id,
                'Обновление статуса абонента',
                $status
                    ? ('Услуга ' . $translate . ' заблокирована' . ($block->cause ? ('. ' . $block->cause) : ''))
                    : ('Услуга ' . $translate . ' разблокирована'),
                'inbox'
            ))->default()->dispatch();
        }
    }
}