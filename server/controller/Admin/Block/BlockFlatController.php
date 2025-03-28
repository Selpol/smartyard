<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Block;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Block\BlockDeleteRequest;
use Selpol\Controller\Request\Admin\Block\BlockFlatStoreRequest;
use Selpol\Controller\Request\Admin\Block\BlockUpdateRequest;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Inbox\InboxFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

/**
 * Блокировки квартир
 */
#[Controller('/admin/block/flat')]
readonly class BlockFlatController extends AdminRbtController
{
    /**
     * Получить блокировки квартиры
     *
     * @param int $id Идентификатор квартиры
     */
    #[Get('/{id}')]
    public function index(int $id): ResponseInterface
    {
        return self::success(FlatBlock::getRepository()->findByFlatId($id));
    }

    /**
     * Добавить блокировку
     */
    #[Post]
    public function store(BlockFlatStoreRequest $request): ResponseInterface
    {
        $flatBlock = new FlatBlock();

        $flatBlock->flat_id = $request->flat_id;

        $flatBlock->service = $request->service;

        $flatBlock->cause = $request->cause;
        $flatBlock->comment = $request->comment;

        $flatBlock->status = BlockFeature::STATUS_ADMIN;

        if ($flatBlock->safeInsert()) {
            if ($flatBlock->service == BlockFeature::SERVICE_INTERCOM || $flatBlock->service == BlockFeature::SUB_SERVICE_CMS) {
                task(new IntercomSyncFlatTask(-1, $flatBlock->flat_id, false))->high()->dispatch();
            }

            if ($request->notify) {
                self::notify($flatBlock, true);
            }

            return self::success($flatBlock->id);
        }

        return self::error('Не удалось создать блокировку квартиры', 400);
    }

    /**
     * Обновить блокировку
     */
    #[Put('/{id}')]
    public function update(BlockUpdateRequest $request): ResponseInterface
    {
        $flatBlock = FlatBlock::findById($request->id, setting: setting()->nonNullable());

        $flatBlock->cause = $request->cause;
        $flatBlock->comment = $request->comment;

        if ($flatBlock->safeUpdate()) {
            if ($flatBlock->service == BlockFeature::SERVICE_INTERCOM || $flatBlock->service == BlockFeature::SUB_SERVICE_CMS) {
                task(new IntercomSyncFlatTask(-1, $flatBlock->flat_id, false))->high()->dispatch();
            }

            if ($request->notify) {
                self::notify($flatBlock, true);
            }

            return self::success($flatBlock->id);
        }

        return self::error('Не удалось обновить блокировку квартиры', 400);
    }

    /**
     * Удалить блокировку
     */
    #[Delete('/{id}')]
    public function delete(BlockDeleteRequest $request): ResponseInterface
    {
        $flatBlock = FlatBlock::findById($request->id, setting: setting()->nonNullable());

        if ($flatBlock->status == BlockFeature::STATUS_BILLING && !container(AuthService::class)->checkScope('block-flat-billing-delete')) {
            return self::error('Не удалось удалить блокировку квартиры', 400);
        }

        if ($flatBlock->safeDelete()) {
            if ($flatBlock->service == BlockFeature::SERVICE_INTERCOM || $flatBlock->service == BlockFeature::SUB_SERVICE_CMS) {
                task(new IntercomSyncFlatTask(-1, $flatBlock->flat_id, false))->high()->dispatch();
            }

            if ($request->notify) {
                self::notify($flatBlock, false);
            }

            return self::success();
        }

        return self::error('Не удалось удалить блокировку квартиры', 400);
    }

    private static function notify(FlatBlock $block, bool $status): void
    {
        $translate = BlockController::translate($block->service);

        if ($translate) {
            task(new InboxFlatTask(
                $block->flat_id,
                'Обновление статуса квартиры',
                $status
                    ? ('Услуга ' . $translate . ' заблокирована' . ($block->cause ? ('. ' . $block->cause) : ''))
                    : ('Услуга ' . $translate . ' разблокирована'),
                'inbox'
            ))->default()->dispatch();
        }
    }
}