<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Service\AuthService;

readonly class reset extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'type' => rule()->string()->in(['reset', 'key', 'apartment'])
        ]);

        $intercom = intercom($validate['_id']);

        if ($intercom instanceof IntercomDevice) {
            file_logger('intercom')->debug('Сброс домофона', ['id' => $params['_id'], 'user' => container(AuthService::class)->getUserOrThrow()->getIdentifier()]);

            if (!$intercom->ping()) {
                return self::error('Устройство не доступно', 404);
            }

            switch ($validate['type']) {
                case 'key':
                    if ($intercom instanceof KeyInterface) {
                        $intercom->clearKey();;
                    }

                    break;
                case 'apartment':
                    if ($intercom instanceof ApartmentInterface) {
                        $intercom->clearApartments();
                    }

                    break;
                case 'reset':
                default:
                    $intercom->reset();
                    break;
            }

            if (container(AuditFeature::class)->canAudit()) {
                container(AuditFeature::class)->audit(strval($params['_id']), DeviceIntercom::class, 'reset', 'Сброс домофона');
            }

            return self::success();
        }

        return self::error('Домофон не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Deprecated] [Домофон] Сброс домофона'];
    }
}