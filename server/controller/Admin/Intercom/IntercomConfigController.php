<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Intercom\IntercomConfigShowRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomConfigUpdateRequest;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Intercom\Flat\IntercomAudioFlatTask;

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

        $intercom = intercom($request->id);

        if (str_starts_with($request->key, 'audio.volume')) {
            return self::success($intercom->resolver->string($request->key, $intercom->resolver->string('audio.volume')));
        }

        return self::success($intercom->resolver->string($request->key, ''));
    }

    /**
     * Обновить настройку домофона
     */
    #[Put('/{id}')]
    public function update(IntercomConfigUpdateRequest $request, ConfigFeature $feature, AuthService $service): ResponseInterface
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

        $intercom->config = (string)$config->set($request->key, $request->value);

        $intercom->update();

        $feature->clearCacheConfigForIntercom($request->id);

        if (str_starts_with($request->key, 'audio.volume')) {
            task(new IntercomAudioFlatTask($request->id, intval(substr($request->key, 13))))->high()->dispatch();
        }

        return self::success();
    }
}
