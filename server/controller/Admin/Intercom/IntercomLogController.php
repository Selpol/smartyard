<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Intercom\IntercomLogIndexRequest;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Домофон-Логи
 */
#[Controller('/admin/intercom/log')]
readonly class IntercomLogController extends AdminRbtController
{
    /**
     * Получить логи с домофона
     * */
    #[Get('/{id}')]
    public function index(IntercomLogIndexRequest $request, PlogFeature $featue): ResponseInterface
    {
        $intercom = DeviceIntercom::findById($request->id);

        if (!$intercom) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (is_null($intercom->ip)) {
            return self::error('Не удалось определить IP-адрес', 404);
        }

        $logs = $featue->getSyslogFilter(
            $intercom->ip,
            $request->message,
            $request->min_date,
            $request->max_date,
            $request->page,
            $request->size
        );

        return self::success($logs ?: []);
    }
}
