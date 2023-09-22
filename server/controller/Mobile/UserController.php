<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Push\PushFeature;
use Selpol\Http\Response;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;

class UserController extends Controller
{
    public function ping(): Response
    {
        $this->getSubscriber();

        return $this->rbtResponse();
    }

    public function registerPushToken(): Response
    {
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), [
            'pushToken' => [Rule::length(16), Filter::fullSpecialChars()],
            'voipToken' => [Rule::length(16), Filter::fullSpecialChars()],
            'production' => [Filter::default(false), Rule::bool(), Rule::nonNullable()],
            'platform' => [Rule::in(['ios', 'android', 'huawei'])]
        ]);

        $households = container(HouseFeature::class);

        $old_push = $user["pushToken"];

        $production = trim($validate['production']);

        if (!array_key_exists('platform', $validate) || ($validate['platform'] != 'ios' && $validate['platform'] != 'android' && $validate['platform'] != 'huawei'))
            return $this->rbtResponse(400, message: 'Неверный тип платформы');

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

        return $this->rbtResponse();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function sendName(): Response
    {
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), [
            'name' => [Rule::required(), Filter::fullSpecialChars(), Rule::length(max: 64), Rule::nonNullable()],
            'patronymic' => [Filter::fullSpecialChars(), Rule::length(max: 64)]
        ]);

        if ($validate['patronymic']) container(HouseFeature::class)->modifySubscriber($user['subscriberId'], ["subscriberName" => $validate['name'], "subscriberPatronymic" => $validate['patronymic']]);
        else container(HouseFeature::class)->modifySubscriber($user["subscriberId"], ["subscriberName" => $validate['name']]);

        return $this->rbtResponse();
    }
}