<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\User;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\RedisService;

/**
 * Пользователь настройки
 */
#[Controller('/admin/user/setting')]
readonly class UserSettingController extends AdminRbtController
{
    /**
     * Получить настройки пользователя
     */
    #[Get]
    public function index(SipFeature $feature, RedisService $service): ResponseInterface
    {
        $password = $service->get('user:' . $this->getUser()->getIdentifier() . ':ws');

        if (!$password) {
            $password = md5(guid_v4());
        }

        $service->setEx('user:' . $this->getUser()->getIdentifier() . ':ws', 24 * 60 * 60, $password);

        $sipServer = $feature->server('first')[0];

        return self::success([
            'uid' => $this->getUser()->getIdentifier(),
            'login' => $this->getUser()->getUsername(),

            'wsTitle' => $sipServer->title,
            'wsDomain' => $sipServer->external_ip,

            'wsUsername' => sprintf('7%09d', (int) $this->getUser()->getIdentifier()),
            'wsPassword' => $password,
        ]);
    }
}