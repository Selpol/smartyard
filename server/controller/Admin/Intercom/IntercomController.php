<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Intercom\IntercomIndexRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomStoreRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomUpdateRequest;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;

/**
 * Домофон
 */
#[Controller('/admin/intercom')]
readonly class IntercomController extends AdminRbtController
{
    /**
     * Получить список домофонов
     */
    #[Get]
    public function index(IntercomIndexRequest $request, AuthService $service): ResponseInterface
    {
        $criteria = criteria()
            ->like('comment', $request->comment)
            ->equal('model', $request->model)
            ->like('ip', $request->ip)
            ->like('device_id', $request->device_id)
            ->like('device_model', $request->device_model)
            ->like('device_software_version', $request->device_software_version)
            ->like('device_hardware_version', $request->device_hardware_version)
            ->asc('house_domophone_id');

        if (!$service->checkScope('intercom-hidden')) {
            $criteria->equal('hidden', false);
        }

        return self::success(DeviceIntercom::fetchPage($request->page, $request->size, $criteria));
    }

    /**
     * Получить домофон
     * 
     * @param int $id Идентификатор домофона
     */
    #[Get('/{id}')]
    public function show(int $id, AuthService $service): ResponseInterface
    {
        $criteria = criteria();

        if (!$service->checkScope('intercom-hidden')) {
            $criteria->equal('hidden', false);
        }

        $intercom = DeviceIntercom::findById($id);

        if (!$intercom) {
            return self::error('Не удалось найти домофон', 404);
        }

        return self::success($intercom);
    }

    /**
     * Создать новый домофон
     */
    #[Post]
    public function store(IntercomStoreRequest $request): ResponseInterface
    {
        $intercom = new DeviceIntercom();

        $intercom->enabled = $request->enabled;

        $intercom->model = $request->model;
        $intercom->server = $request->server;
        $intercom->url = $request->url;
        $intercom->credentials = $request->credentials;
        $intercom->dtmf = $request->dtmf;

        $intercom->nat = $request->nat;

        $intercom->ip = $request->ip;

        $intercom->comment = $request->comment;

        $intercom->config = $request->config;

        $intercom->hidden = $request->hidden;

        $intercom->insert();

        return self::success($intercom->house_domophone_id);
    }

    /**
     * Обновить домофон
     */
    #[Put('/{id}')]
    public function update(IntercomUpdateRequest $request, ConfigFeature $feature): ResponseInterface
    {
        $intercom = DeviceIntercom::findById($request->id);

        if (!$intercom) {
            return self::error('Не удалось найти домофон', 404);
        }

        $intercom->enabled = $request->enabled;

        $intercom->model = $request->model;
        $intercom->server = $request->server;
        $intercom->url = $request->url;
        $intercom->credentials = $request->credentials;
        $intercom->dtmf = $request->dtmf;

        $intercom->nat = $request->nat;

        $intercom->ip = $request->ip;

        $intercom->comment = $request->comment;

        $intercom->config = $request->config;

        $intercom->hidden = $request->hidden;

        $intercom->update();

        $feature->clearCacheConfigForIntercom($request->id);

        return self::success();
    }

    /**
     * Удалить домофон
     * 
     * @param int $id Идентификатор домофона
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $intercom = DeviceIntercom::findById($id);

        if (!$intercom) {
            return self::error('Не удалось найти домофон', 404);
        }

        $intercom->delete();

        return self::success();
    }
}
