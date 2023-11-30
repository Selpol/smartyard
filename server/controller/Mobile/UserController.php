<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\UserRegisterPushTokenRequest;
use Selpol\Controller\Request\Mobile\UserSendNameRequest;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\External\ExternalFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;

#[Controller('/mobile/user')]
readonly class UserController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/ping')]
    public function ping(): Response
    {
        $this->getUser();

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/registerPushToken')]
    public function registerPushToken(UserRegisterPushTokenRequest $request): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $households = container(HouseFeature::class);

        $old_push = $user["pushToken"];

        if ($request->platform == 'ios') {
            $platform = 1;

            $type = $request->production ? 1 : 2; // apn : apn.dev
        } elseif ($request->platform == 'huawei') {
            $platform = 0;

            $type = 3; // huawei
        } else {
            $platform = 0;

            $type = 0; // fcm
        }

        $households->modifySubscriber($user["subscriberId"], ["pushToken" => $request->pushToken, "tokenType" => $type, "voipToken" => $request->voipToken, "voipEnabled" => $request->voipEnabled, "platform" => $platform]);

        if (!$request->pushToken)
            $households->modifySubscriber($user["subscriberId"], ["pushToken" => "off"]);
        else if ($old_push && $old_push != $request->pushToken)
            container(ExternalFeature::class)->logout(["token" => $old_push, "msg" => "Произведена авторизация на другом устройстве", "pushAction" => "logout"]);

        if (!$request->voipToken)
            $households->modifySubscriber($user["subscriberId"], ["voipToken" => "off"]);

        return user_response();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/sendName')]
    public function sendName(UserSendNameRequest $request): Response
    {
        $userId = $this->getUser()->getIdentifier();

        if ($request->patronymic) container(HouseFeature::class)->modifySubscriber($userId, ["subscriberName" => $request->name, "subscriberPatronymic" => $request->patronymic]);
        else container(HouseFeature::class)->modifySubscriber($userId, ["subscriberName" => $request->name]);

        return user_response();
    }
}