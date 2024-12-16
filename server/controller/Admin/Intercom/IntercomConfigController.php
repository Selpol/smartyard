<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Intercom\IntercomConfigShowRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomConfigUpdateRequest;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Config;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;

/**
 * Конфигурация домофона
 */
#[Controller('/admin/intercom/config')]
readonly class IntercomConfigController extends AdminRbtController
{
    /**
     * Получить настройку домофона
     */
    #[Get('/{id}')]
    public function show(IntercomConfigShowRequest $request, AuthService $service): ResponseInterface
    {
        if (str_starts_with($request->key, 'audio.volume')) {
            if (!$service->checkScope('intercom-config-audio')) {
                return self::error('Не хватает прав на управление аудио', 403);
            }
        } else {
            return self::error('Не доступный ключ конфигурации', 403);
        }

        $intercom = DeviceIntercom::findById($request->id, setting: setting()->nonNullable());
        $config = new Config();

        if ($intercom->config) {
            $config->load($intercom->config);
        }

        return self::success($config->resolve($request->key));
    }

    /**
     * Обновить настройку домофона
     */
    #[Put('/{id}')]
    public function update(IntercomConfigUpdateRequest $request, AuthService $service): ResponseInterface
    {
        if (str_starts_with($request->key, 'audio.volume')) {
            if (!$service->checkScope('intercom-config-audio')) {
                return self::error('Не хватает прав на управление аудио', 403);
            }
        } else {
            return self::error('Не доступный ключ конфигурации', 403);
        }

        $intercom = DeviceIntercom::findById($request->id, setting: setting()->nonNullable());
        $config = new Config();

        if ($intercom->config) {
            $config->load($intercom->config);
        }

        $intercom->config = implode(PHP_EOL, $config->set($request->key, $request->value)->getValues());

        $intercom->update();

        return self::success();
    }
}
