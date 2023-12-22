<?php declare(strict_types=1);

namespace Selpol\Controller\Api\block;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Http\Response;
use Selpol\Task\Tasks\Inbox\InboxFlatTask;

readonly class subscriber extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        return self::success(SubscriberBlock::getRepository()->findBySubscriberId($params['_id']));
    }

    public static function POST(array $params): array|Response|ResponseInterface
    {
        $subscriberBlock = new SubscriberBlock(validator($params, [
            'flat_id' => rule()->id(),

            'service' => rule()->required()->in([0, 1, 2, 3, 4, 5, 6])->nonNullable(),
            'status' => rule()->required()->in([1, 2, 3])->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),
        ]));

        if ($subscriberBlock->insert()) {
            self::notify($subscriberBlock, true);

            return self::success($subscriberBlock->id);
        }

        return self::error('Не удалось создать блокировку абонента', 400);
    }

    public static function PUT(array $params): array|Response|ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'service' => rule()->required()->in([0, 1, 2, 3, 4, 5, 6])->nonNullable(),
            'status' => rule()->required()->in([1, 2, 3])->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),
        ]);

        $subscriberBlock = SubscriberBlock::findById($validate['_id'], setting: setting()->nonNullable());

        $subscriberBlock->service = $validate['service'];
        $subscriberBlock->status = $validate['status'];

        $subscriberBlock->cause = $validate['cause'];
        $subscriberBlock->comment = $validate['comment'];

        if ($subscriberBlock->update()) {
            self::notify($subscriberBlock, true);

            return self::success($subscriberBlock->id);
        }

        return self::error('Не удалось обновить блокировку абонента', 400);
    }

    public static function DELETE(array $params): array|Response|ResponseInterface
    {
        $subscriberBlock = SubscriberBlock::findById($params['_id'], setting: setting()->nonNullable());

        if ($subscriberBlock->delete()) {
            self::notify($subscriberBlock, false);

            return self::success();
        }

        return self::error('Не удалось удалить блокировку абонента', 400);
    }

    public static function index(): array|bool
    {
        return [
            'GET' => '[Блокировка-Абонент] Получить список',
            'POST' => '[Блокировка-Абонент] Добавить блокировку',
            'PUT' => '[Блокировка-Абонент] Обновить блокировку',
            'DELETE' => '[Блокировка-Абонент] Удалить блокировку'
        ];
    }

    private static function notify(SubscriberBlock $block, bool $status): void
    {
        task(new InboxFlatTask(
            $block->subscriber_id,
            'Обновление статуса абонента',
            $status
                ? ('Услуга ' . self::translate($block->service) . ' заблокирована' . ($block->cause ? ('. ' . $block->cause) : ''))
                : ('Услуга ' . self::translate($block->service) . ' разблокирована'),
            'inbox'
        ))->low()->dispatch();
    }

    private static function translate(int $value): string
    {
        return match ($value) {
            BlockFeature::SERVICE_INTERCOM => 'умного домофона',
            BlockFeature::SERVICE_CCTV => 'видеонаблюдения',

            BlockFeature::SUB_SERVICE_CALL => 'видеозвонков',
            BlockFeature::SUB_SERVICE_OPEN => 'открытия двери',
            BlockFeature::SUB_SERVICE_EVENT => 'событий',
            BlockFeature::SUB_SERVICE_ARCHIVE => 'архива',
            BlockFeature::SUB_SERVICE_FRS => 'распознования лиц',

            default => '',
        };
    }
}