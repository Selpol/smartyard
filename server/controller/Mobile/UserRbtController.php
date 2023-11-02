<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Push\PushFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;

#[Controller('/mobile/user')]
readonly class UserRbtController extends RbtController
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
    public function registerPushToken(ServerRequestInterface $request): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($request->getParsedBody(), [
            'pushToken' => [filter()->fullSpecialChars(), rule()->clamp(16)],
            'voipToken' => [filter()->fullSpecialChars(), rule()->clamp(16)],
            'production' => [filter()->default(false), rule()->bool()],
            'platform' => rule()->in(['ios', 'android', 'huawei'])
        ]);

        $households = container(HouseFeature::class);

        $old_push = $user["pushToken"];

        $production = trim($validate['production']);

        if (!array_key_exists('platform', $validate) || ($validate['platform'] != 'ios' && $validate['platform'] != 'android' && $validate['platform'] != 'huawei'))
            return user_response(400, message: 'Неверный тип платформы');

        if ($validate['platform'] == 'ios') {
            $platform = 1;
            if ($validate['voipToken']) {
                $type = $production ? 1 : 2; // apn:apn.dev
            } else {
                $type = 0; // fcm (resend)
            }
        } elseif ($validate['platform'] == 'huawei') {
            $platform = 0;
            $type = 3; // huawei
        } else {
            $platform = 0;
            $type = 0; // fcm
        }

        $households->modifySubscriber($user["subscriberId"], ["pushToken" => $validate['pushToken'], "tokenType" => $type, "voipToken" => $validate['voipToken'], "platform" => $platform]);

        if (!$validate['pushToken'])
            $households->modifySubscriber($user["subscriberId"], ["pushToken" => "off"]);
        else if ($old_push && $old_push != $validate['pushToken'])
            container(PushFeature::class)->logout(["token" => $old_push, "msg" => "Произведена авторизация на другом устройстве", "pushAction" => "logout"]);

        if (!$validate['voipToken'])
            $households->modifySubscriber($user["subscriberId"], ["voipToken" => "off"]);

        return user_response();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/sendName')]
    public function sendName(ServerRequestInterface $request): Response
    {
        $userId = $this->getUser()->getIdentifier();

        $validate = validator($request->getParsedBody(), [
            'name' => [filter()->fullSpecialChars(), rule()->required()->string()->max(64)->nonNullable()],
            'patronymic' => [filter()->fullSpecialChars(), rule()->string()->max(64)]
        ]);

        if ($validate['patronymic']) container(HouseFeature::class)->modifySubscriber($userId, ["subscriberName" => $validate['name'], "subscriberPatronymic" => $validate['patronymic']]);
        else container(HouseFeature::class)->modifySubscriber($userId, ["subscriberName" => $validate['name']]);

        return user_response();
    }
}