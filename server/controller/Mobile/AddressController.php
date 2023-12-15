<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\AddressRegisterQrRequest;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\External\ExternalFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\SubscriberMiddleware;

#[Controller('/mobile/address')]
readonly class AddressController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/getAddressList')]
    public function getAddressList(HouseFeature $houseFeature, CameraFeature $cameraFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $houses = [];

        foreach ($user['flats'] as $flat) {
            $houseId = $flat['addressHouseId'];

            $flatDetail = $houseFeature->getFlat($flat["flatId"]);
            $block = $flatDetail['autoBlock'] || $flatDetail['adminBlock'] || $flatDetail['manualBlock'];

            if (array_key_exists($houseId, $houses)) $house = &$houses[$houseId];
            else {
                $houses[$houseId] = [];
                $house = &$houses[$houseId];

                $house['houseId'] = strval($houseId);
                $house['address'] = $flat['house']['houseFull'];

                $is_owner = ((int)$flat['role'] == 0);

                $has_plog = $flatDetail['plog'] == PlogFeature::ACCESS_ALL || $flatDetail['plog'] == PlogFeature::ACCESS_OWNER_ONLY && $is_owner;

                if ($flatDetail['plog'] != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN)
                    $house['hasPlog'] = $has_plog;

                $house['cameras'] = $houseFeature->getCameras("houseId", $houseId);
                $house['doors'] = [];
            }

            if (!array_key_exists('flats', $house))
                $house['flats'] = [];

            $house['flats'][] = ['id' => $flat['flatId'], 'flat' => $flat['flat'], 'owner' => $flat['role'] == 0, 'block' => $block, 'description' => $flat['descriptionBlock']];

            $house['cameras'] = array_merge($house['cameras'], $houseFeature->getCameras("flatId", $flat['flatId']));
            $house['cctv'] = count($house['cameras']);

            foreach ($flatDetail['entrances'] as $entrance) {
                if (array_key_exists($entrance['entranceId'], $house['doors']))
                    continue;

                $e = $houseFeature->getEntrance($entrance['entranceId']);

                $door = [];

                $door['domophoneId'] = strval($e['domophoneId']);
                $door['doorId'] = intval($e['domophoneOutput']);
                $door['cameraId'] = intval($e['cameraId']);

                $door['icon'] = $e['entranceType'];
                $door['name'] = $e['entrance'];

                if ($e['cameraId']) {
                    $cam = $cameraFeature->getCamera($e["cameraId"]);

                    $house['cameras'][] = $cam;
                    $house['cctv']++;
                }

                $door['block'] = $block;

                $house['doors'][$entrance['entranceId']] = $door;
            }

            usort($house['doors'], static fn(array $a, array $b) => strcmp($a['name'], $b['name']));
        }

        foreach ($houses as $house_key => $h) {
            $houses[$house_key]['doors'] = array_values($h['doors']);

            unset($houses[$house_key]['cameras']);
        }

        $result = array_values($houses);

        if (count($result)) {
            usort($result, static fn(array $a, array $b) => strcmp($a['address'], $b['address']));

            return user_response(data: $result);
        }

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/registerQR', excludes: [SubscriberMiddleware::class])]
    public function registerQR(AddressRegisterQrRequest $request, HouseFeature $houseFeature, ExternalFeature $externalFeature): Response
    {
        $token = $this->getToken();

        $audJti = $token->getOriginalValue()['scopes'][1];

        $hash = '';

        for ($i = strlen($request->QR) - 1; $i >= 0; --$i) {
            if (!in_array($request->QR[$i], ['/', '=', "_"]))
                $hash = $request->QR[$i] . $hash;
            else
                break;
        }

        if ($hash == '')
            return user_response(200, "QR-код не является кодом для доступа к квартире");

        $flat = HouseFlat::getRepository()->findByCode($hash);

        if (!$flat)
            return user_response(200, "QR-код не является кодом для доступа к квартире");

        $subscribers = $houseFeature->getSubscribers('aud_jti', $audJti);

        if (!$subscribers || count($subscribers) === 0) {
            $mobile = $request->mobile;

            $name = $request->name;
            $patronymic = $request->patronymic;

            if (strlen($mobile) !== 11)
                return user_response(400, false, message: 'Неверный формат номера телефона');

            if (!$name) return user_response(400, message: 'Имя обязательно для заполнения');
            if (!$patronymic) return user_response(400, message: 'Отчество обязательно для заполнения');

            if ($houseFeature->addSubscriber($mobile, $name, $patronymic)) {
                $subscribers = $houseFeature->getSubscribers('mobile', $mobile);

                if (count($subscribers) > 0)
                    $houseFeature->modifySubscriber($subscribers[0]['subscriberId'], ['audJti' => $audJti]);
            } else return user_response(422, 'Не удалось зарегестрироваться');
        }

        if ($subscribers && count($subscribers) > 0) {
            $subscriber = $subscribers[0];

            foreach ($subscriber['flats'] as $item)
                if ((int)$item['flatId'] == $flat->house_flat_id)
                    return user_response(200, "У вас уже есть доступ к данной квартире");

            if ($houseFeature->addSubscriber($subscriber["mobile"], flatId: $flat->house_flat_id)) {
                $house = AddressHouse::findById($flat->address_house_id, setting: setting()->nonNullable());

                $result = $externalFeature->qr($house->house_uuid, $flat->flat, substr((string)$request->mobile, 1), $request->name . ' ' . $request->patronymic, connection_ip($request->getRequest()));

                if (is_string($result))
                    return user_response(400, message: $result);

                if ($result)
                    return user_response(200, "Квартира успешно привязана");

                return user_response(400, message: 'Не удалось полностью выполнить запрос, пользователь не привязан полностью');
            } else return user_response(422, message: 'Неудалось добавиться в квартиру');
        } else return user_response(404, message: 'Абонент не найден');
    }
}