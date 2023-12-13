<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;

readonly class sync extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $deviceIntercom = DeviceIntercom::findById(
            rule()->id()->onItem('_id', $params),
            setting: setting()->columns(['house_domophone_id'])
        );

        if ($deviceIntercom) {
            task(new IntercomConfigureTask($deviceIntercom->house_domophone_id))->high()->dispatch();

            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit(strval($deviceIntercom->house_domophone_id), DeviceIntercom::class, 'sync', 'Синхронизация домофона');

            return self::success();
        }

        return self::error('Домофон не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Синхронизация домофона'];
    }
}