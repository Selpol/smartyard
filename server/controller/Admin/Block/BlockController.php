<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Block;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Блокировки
 */
#[Controller('/admin/block')]
readonly class BlockController extends AdminRbtController
{
    public const SERVICES_FLAT = [
        BlockFeature::SERVICE_INTERCOM,
        BlockFeature::SERVICE_CCTV,

        BlockFeature::SUB_SERVICE_CALL,
        BlockFeature::SUB_SERVICE_OPEN,
        BlockFeature::SUB_SERVICE_EVENT,
        BlockFeature::SUB_SERVICE_ARCHIVE,
        BlockFeature::SUB_SERVICE_FRS,
        BlockFeature::SUB_SERVICE_CMS,

        BlockFeature::SUB_SERVICE_APP
    ];

    public const SERVICES_SUBSCRIBER = [
        BlockFeature::SERVICE_INTERCOM,
        BlockFeature::SERVICE_CCTV,

        BlockFeature::SUB_SERVICE_CALL,
        BlockFeature::SUB_SERVICE_OPEN,
        BlockFeature::SUB_SERVICE_EVENT,
        BlockFeature::SUB_SERVICE_ARCHIVE,
        BlockFeature::SUB_SERVICE_FRS,
        BlockFeature::SUB_SERVICE_INBOX,

        BlockFeature::SUB_SERVICE_APP
    ];

    /**
     * Получить список блокировок
     */
    #[Get]
    public function index(): ResponseInterface
    {
        return self::success([
            'services' => [
                BlockFeature::SERVICE_INTERCOM => 'Домофония',
                BlockFeature::SERVICE_CCTV => 'Видеонаблюдение',

                BlockFeature::SUB_SERVICE_CALL => 'Видеозвонки',
                BlockFeature::SUB_SERVICE_OPEN => 'Открытие домофона',
                BlockFeature::SUB_SERVICE_EVENT => 'События',
                BlockFeature::SUB_SERVICE_ARCHIVE => 'Архив',
                BlockFeature::SUB_SERVICE_FRS => 'Распозвонания лиц',
                BlockFeature::SUB_SERVICE_CMS => 'Трубка домофона',
                BlockFeature::SUB_SERVICE_INBOX => 'Сообщения',

                BlockFeature::SUB_SERVICE_APP => 'Приложение'
            ],

            'services_flat' => self::SERVICES_FLAT,
            'services_subscriber' => self::SERVICES_SUBSCRIBER,

            'status' => [
                BlockFeature::STATUS_ADMIN => 'Администратор',
                BlockFeature::STATUS_BILLING => 'Биллинг',
                BlockFeature::STATUS_ADMIN | BlockFeature::STATUS_BILLING => 'Администратор | Биллинг'
            ]
        ]);
    }

    public static function translate(int $value): string
    {
        return match ($value) {
            BlockFeature::SERVICE_INTERCOM => 'умного домофона',
            BlockFeature::SERVICE_CCTV => 'видеонаблюдения',

            BlockFeature::SUB_SERVICE_CALL => 'видеозвонков',
            BlockFeature::SUB_SERVICE_OPEN => 'открытия двери',
            BlockFeature::SUB_SERVICE_EVENT => 'событий',
            BlockFeature::SUB_SERVICE_ARCHIVE => 'архива',
            BlockFeature::SUB_SERVICE_FRS => 'распознования лиц',
            BlockFeature::SUB_SERVICE_CMS => 'звонков в квартиру',

            BlockFeature::SUB_SERVICE_APP => 'приложения',

            default => '',
        };
    }
}