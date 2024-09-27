<?php declare(strict_types=1);

namespace Selpol\Controller\Api\block;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Http\Response;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Inbox\InboxFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

readonly class flat extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        return self::success(FlatBlock::getRepository()->findByFlatId(rule()->id()->onItem('_id', $params)));
    }

    public static function POST(array $params): array|Response|ResponseInterface
    {
        $flatBlock = new FlatBlock(validator($params, [
            'flat_id' => rule()->id(),

            'service' => rule()->required()->in(block::SERVICES_FLAT)->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),
        ]));

        $flatBlock->status = BlockFeature::STATUS_ADMIN;

        if ($flatBlock->insert()) {
            if ($flatBlock->service == BlockFeature::SERVICE_INTERCOM || $flatBlock->service == BlockFeature::SUB_SERVICE_CMS) {
                task(new IntercomSyncFlatTask(-1, $flatBlock->flat_id, false))->high()->dispatch();
            }

            if (array_key_exists('notify', $params) && $params['notify']) {
                self::notify($flatBlock, true);
            }

            return self::success($flatBlock->id);
        }

        return self::error('Не удалось создать блокировку квартиры', 400);
    }

    public static function PUT(array $params): array|Response|ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),
        ]);

        $flatBlock = FlatBlock::findById($validate['_id'], setting: setting()->nonNullable());

        $flatBlock->cause = $validate['cause'];
        $flatBlock->comment = $validate['comment'];

        if ($flatBlock->update()) {
            if ($flatBlock->service == BlockFeature::SERVICE_INTERCOM || $flatBlock->service == BlockFeature::SUB_SERVICE_CMS) {
                task(new IntercomSyncFlatTask(-1, $flatBlock->flat_id, false))->high()->dispatch();
            }

            if (array_key_exists('notify', $params) && $params['notify']) {
                self::notify($flatBlock, true);
            }

            return self::success($flatBlock->id);
        }

        return self::error('Не удалось обновить блокировку квартиры', 400);
    }

    public static function DELETE(array $params): array|Response|ResponseInterface
    {
        $flatBlock = FlatBlock::findById($params['_id'], setting: setting()->nonNullable());

        if ($flatBlock->status == BlockFeature::STATUS_BILLING && !container(AuthService::class)->checkScope('block-flat-billing-delete')) {
            return self::error('Не удалось удалить блокировку квартиры', 400);
        }

        if ($flatBlock->delete()) {
            if ($flatBlock->service == BlockFeature::SERVICE_INTERCOM || $flatBlock->service == BlockFeature::SUB_SERVICE_CMS) {
                task(new IntercomSyncFlatTask(-1, $flatBlock->flat_id, false))->high()->dispatch();
            }

            if (array_key_exists('notify', $params) && $params['notify']) {
                self::notify($flatBlock, false);
            }

            return self::success();
        }

        return self::error('Не удалось удалить блокировку квартиры', 400);
    }

    public static function index(): array|bool
    {
        return [
            'GET' => '[Блокировка-Квартира] Получить список',
            'POST' => '[Блокировка-Квартира] Добавить блокировку',
            'PUT' => '[Блокировка-Квартира] Обновить блокировку',
            'DELETE' => '[Блокировка-Квартира] Удалить блокировку'
        ];
    }

    private static function notify(FlatBlock $block, bool $status): void
    {
        task(new InboxFlatTask(
            $block->flat_id,
            'Обновление статуса квартиры',
            $status
                ? ('Услуга ' . block::translate($block->service) . ' заблокирована' . ($block->cause ? ('. ' . $block->cause) : ''))
                : ('Услуга ' . block::translate($block->service) . ' разблокирована'),
            'inbox'
        ))->default()->dispatch();
    }
}