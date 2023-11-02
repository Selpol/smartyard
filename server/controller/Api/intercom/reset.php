<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Service\AuthService;

readonly class reset extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $intercom = intercom(intval(rule()->id()->onItem('_id', $params)));

        if ($intercom) {
            file_logger('intercom')->debug('Сброс домофона', ['id' => $params['_id'], 'user' => container(AuthService::class)->getUserOrThrow()->getIdentifier()]);

            if (!$intercom->ping())
                return self::error('Устройство не доступно', 404);

            $intercom->reset();

            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit(strval($params['_id']), DeviceIntercom::class, 'reset', 'Сброс домофона');

            return self::success();
        }

        return self::error('Домофон не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Сброс домофона'];
    }
}