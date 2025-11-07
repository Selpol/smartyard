<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\UserRegisterPushTokenRequest;
use Selpol\Controller\Request\Mobile\UserSendNameRequest;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\External\ExternalFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;

#[Controller('/mobile/user')]
readonly class UserController extends MobileRbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/ping')]
    public function ping(): Response
    {
        $user = $this->getUser();

        return user_response(data: ['role' => $user->getOriginalValue()['role']]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/registerPushToken')]
    public function registerPushToken(UserRegisterPushTokenRequest $request, ExternalFeature $externalFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $old_push = $user["pushToken"];

        if ($request->platform == 'ios') {
            $platform = 1;

            $type = $request->production ? 1 : 2; // apn : apn.dev
        } elseif ($request->platform == 'huawei') {
            $platform = 0;

            $type = 3;
        } elseif ($request->platform == 'rustore') {
            $platform = 0;
            $type = 4;
        } else {
            $platform = 0;

            $type = 0; // fcm
        }

        $subscriber = HouseSubscriber::findById($user['subscriberId'], setting: setting()->nonNullable());

        $subscriber->push_token = $request->pushToken;
        $subscriber->push_token_type = $type;
        $subscriber->voip_token = $request->voipToken;
        $subscriber->voip_enabled = $request->voipEnabled;
        $subscriber->platform = $platform;

        if (!$request->pushToken) {
            $subscriber->push_token = 'off';
        } else if ($old_push && $old_push != $request->pushToken) {
            $externalFeature->logout(['token' => $old_push, 'msg' => 'Произведена авторизация на другом устройстве', 'pushAction' => 'logout']);
        }

        if (!$request->voipToken) {
            $subscriber->voip_token = 'off';
        }

        if ($request->version) {
            $subscriber->push_version = $request->version;
        }

        if ($subscriber->safeUpdate()) {
            return user_response();
        } else {
            return user_response(400);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/sendName')]
    public function sendName(UserSendNameRequest $request): Response
    {
        $userId = $this->getUser()->getIdentifier();

        $subscriber = HouseSubscriber::findById($userId, setting: setting()->nonNullable());

        if ($request->patronymic) {
            $subscriber->subscriber_name = $request->name;
            $subscriber->subscriber_patronymic = $request->patronymic;
        } else {
            $subscriber->subscriber_name = $request->name;
        }

        if ($subscriber->safeUpdate()) {
            return user_response();
        } else {
            return user_response(400);
        }
    }
}