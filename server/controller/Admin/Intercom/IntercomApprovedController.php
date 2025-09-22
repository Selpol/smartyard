<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Intercom\IntercomApprovedStoreRequest;
use Selpol\Feature\Intercom\IntercomFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Домофон-Проведение
 */
#[Controller('/admin/intercom/approved')]
readonly class IntercomApprovedController extends AdminRbtController
{
    /**
     * Получить домофоны для проведения
     * */
    #[Get]
    public function index(IntercomFeature $feature): ResponseInterface
    {
        return self::success($feature->getApproveds());
    }

    /**
     * Провести домофон
     */
    #[Post('/{ip}')]
    public function store(IntercomApprovedStoreRequest $request, IntercomFeature $feature): ResponseInterface
    {
        $lat = $request->lat;
        $lon = $request->lon;

        if (is_null($request->lat) || is_null($request->lon)) {
            $position = config('position', [0, 0]);

            $lat = $position[0];
            $lon = $position[1];
        }

        $feature->approved(
            $request->ip,
            $request->title,
            $request->name,
            $request->model,
            $request->password,
            $request->server,
            $request->dvr_server_id,
            $request->frs_server_id,
            $request->address_house_id,
            $lat,
            $lon
        );

        return self::success();
    }

    /**
     * Удалить проведение домофона
     */
    #[Delete('/{ip}')]
    public function delete(string $ip, IntercomFeature $feature): ResponseInterface
    {
        $feature->removeApproved(validate('ip', $ip, rule()->ipV4()->exist()));

        return self::success();
    }
}
