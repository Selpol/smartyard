<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Http\Response;
use Selpol\Service\AuthService;

readonly class reset extends Api
{
    public static function GET(array $params): array|Response
    {
        $intercom = intercom(intval(rule()->id()->onItem('_id', $params)));

        if ($intercom) {
            file_logger('intercom')->debug('Сброс домофона', ['id' => $params['_id'], 'user' => container(AuthService::class)->getUserOrThrow()->getIdentifier()]);

            $intercom->reset();

            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit(strval($params['_id']), DeviceIntercom::class, 'reset', 'Сброс домофона');

            return self::ANSWER();
        }

        return self::FALSE('Домофон не найден');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Сброс домофона'];
    }
}