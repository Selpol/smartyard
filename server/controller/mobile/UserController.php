<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
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

        $households = backend('households');
        $isdn = backend('isdn');

        $old_push = $user["pushToken"];

        $production = trim($validate['production']);

        if (!array_key_exists('platform', $validate) || ($validate['platform'] != 'ios' && $validate['platform'] != 'android' && $validate['platform'] != 'huawei'))
            return $this->rbtResponse(400, message: 'Неверный тип платформы');

        if ($validate['platform'] == 'ios') {
            $platform = 1;
            if ($validate['voipToken']) {
                $type = ($production == false) ? 2 : 1; // apn:apn.dev
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
        else {
            if ($old_push && $old_push != $validate['pushToken']) {
                $md5 = md5($validate['pushToken'] . $old_push);
                $payload = [
                    "token" => $old_push,
                    "messageId" => $md5,
                    "msg" => urlencode("Произведена авторизация на другом устройстве"),
                    "badge" => "1",
                    "pushAction" => "logout"
                ];

                $isdn->push($payload);
            }
        }

        if (!$validate['voipToken'])
            $households->modifySubscriber($user["subscriberId"], ["voipToken" => "off"]);

        return $this->rbtResponse();
    }

    public function sendName(): Response
    {
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), [
            'name' => [Rule::required(), Filter::fullSpecialChars(), Rule::length(max: 64), Rule::nonNullable()],
            'patronymic' => [Filter::fullSpecialChars(), Rule::length(max: 64)]
        ]);

        if ($validate['patronymic']) backend('households')->modifySubscriber($user['subscriberId'], ["subscriberName" => $validate['name'], "subscriberPatronymic" => $validate['patronymic']]);
        else backend('households')->modifySubscriber($user["subscriberId"], ["subscriberName" => $validate['name']]);

        return $this->rbtResponse();
    }
}