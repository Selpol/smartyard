<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Service\AuthService;

readonly class reboot extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $intercom = intercom(intval(rule()->id()->onItem('_id', $params)));

        if ($intercom) {
            file_logger('intercom')->debug('Перезапуск домофона', ['id' => $params['_id'], 'user' => container(AuthService::class)->getUserOrThrow()->getIdentifier()]);

            $intercom->reboot();

            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit(strval($params['_id']), DeviceIntercom::class, 'reboot', 'Перезапуск домофона');

            return self::success();
        }

        return self::error('Домофон не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Перезапустить домофон'];
    }
}