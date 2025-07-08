<?php

namespace Selpol\Controller\Mobile;

use Selpol\Entity\Model\Block\FlatBlock;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\AddressRegisterQrRequest;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\External\ExternalFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\SubscriberMiddleware;

#[Controller('/mobile/address')]
readonly class AddressController extends MobileRbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/getAddressList')]
    public function getAddressList(HouseFeature $houseFeature, BlockFeature $blockFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        if ($blockFeature->getFirstBlockForFlat($this->getUser()->getIdentifier(), [BlockFeature::SUB_SERVICE_APP]) != null) {
            return user_response();
        }

        $subscriberIntercomBlock = $blockFeature->getFirstBlockForUser([BlockFeature::SERVICE_INTERCOM]) != null;
        $subscriberCctvBlock = $blockFeature->getFirstBlockForUser([BlockFeature::SERVICE_CCTV]) != null;
        $subscriberBlock = $subscriberIntercomBlock && $subscriberCctvBlock;

        $subscriberEventBlock = $blockFeature->getFirstBlockForUser([BlockFeature::SUB_SERVICE_EVENT]) != null;

        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SUB_SERVICE_APP]) instanceof FlatBlock) {
                continue;
            }

            $houseId = $flat['addressHouseId'];

            $flatDetail = $houseFeature->getFlat($flat["flatId"]);

            $intercomBlock = $subscriberIntercomBlock || $blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM]) != null;
            $intercomHide = $blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SUB_SERVICE_INTERCOM]) != null;
            $cctvBlock = $subscriberCctvBlock || $blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_CCTV]) != null;
            $block = $subscriberBlock || $intercomBlock && $cctvBlock;

            $eventBlock = $subscriberEventBlock || $blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SUB_SERVICE_EVENT]) != null;

            $is_owner = ((int)$flat['role'] == 0);

            if (array_key_exists($houseId, $houses)) {
                $house = &$houses[$houseId];

                if (!$intercomHide && !$house['hasPlog']) {
                    $house['hasPlog'] = !$eventBlock && ($flatDetail['plog'] == PlogFeature::ACCESS_ALL || $flatDetail['plog'] == PlogFeature::ACCESS_OWNER_ONLY && $is_owner);
                }
            } else {
                $houses[$houseId] = [];
                $house = &$houses[$houseId];

                $house['houseId'] = strval($houseId);

                $segments = explode(', ', $flat['house']['houseFull']);

                if (str_starts_with($segments[0], 'г ') || str_ends_with($segments[0], ' обл')) {
                    unset($segments[0]);
                }

                $house['address'] = implode(', ', $segments);

                $house['hasPlog'] = !$intercomHide && !$eventBlock && ($flatDetail['plog'] == PlogFeature::ACCESS_ALL || $flatDetail['plog'] == PlogFeature::ACCESS_OWNER_ONLY && $is_owner);

                $house['cameras'] = $cctvBlock ? [] : $houseFeature->getCameras("houseId", $houseId);

                $house['flats'] = [];
                $house['doors'] = [];
            }

            $house['flats'][] = ['id' => $flat['flatId'], 'flat' => $flat['flat'], 'cms' => $flat['cmsEnabled'], 'owner' => $flat['role'] == 0, 'block' => $block];

            if (!$cctvBlock) {
                $house['cameras'] = array_merge($house['cameras'], $houseFeature->getCameras("flatId", $flat['flatId']));
            }

            $house['cctv'] = count($house['cameras']);

            if (!$intercomHide && !$intercomBlock) {
                foreach ($flatDetail['entrances'] as $entrance) {
                    if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                        continue;
                    }

                    $e = $houseFeature->getEntrance($entrance['entranceId']);

                    $door = [];

                    $door['domophoneId'] = strval($e['domophoneId']);
                    $door['doorId'] = intval($e['domophoneOutput']);
                    $door['cameraId'] = intval($e['cameraId']);

                    $door['icon'] = $e['entranceType'];
                    $door['name'] = $e['entrance'];

                    if ($e['cameraId']) {
                        $house['cameras'][] = DeviceCamera::findById($e['cameraId'], setting: setting()->nonNullable())->toOldArray();

                        ++$house['cctv'];
                    }

                    $door['block'] = $block;

                    $house['doors'][$entrance['entranceId']] = $door;
                }
            }
        }

        foreach ($houses as $house_key => $h) {
            $houses[$house_key]['doors'] = array_values($h['doors']);

            unset($houses[$house_key]['cameras']);
        }

        $result = array_values($houses);

        if ($result !== []) {
            $counter = count($result);
            for ($i = 0; $i < $counter; ++$i)
                usort($result[$i]['doors'], static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

            usort($result, static fn(array $a, array $b): int => strcmp((string)$a['address'], (string)$b['address']));
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

        $audJti = $token->getAudJti();

        $hash = '';

        for ($i = strlen($request->QR) - 1; $i >= 0; --$i) {
            if (!in_array($request->QR[$i], ['/', '=', "_"])) {
                $hash = $request->QR[$i] . $hash;
            } else {
                break;
            }
        }

        if ($hash === '') {
            return user_response(200, "QR-код не является кодом для доступа к квартире");
        }

        $flat = HouseFlat::getRepository()->findByCode($hash);

        if (!$flat) {
            return user_response(200, "QR-код не является кодом для доступа к квартире");
        }

        $subscribers = $houseFeature->getSubscribers('aud_jti', $audJti);

        if ($subscribers === false || $subscribers === [] || count($subscribers) === 0) {
            $mobile = $request->mobile;

            $name = $request->name;
            $patronymic = $request->patronymic;
            if (strlen($mobile) !== 11) {
                return user_response(400, false, message: 'Неверный формат номера телефона');
            }

            if (!$name) {
                return user_response(400, message: 'Имя обязательно для заполнения');
            }

            if (!$patronymic) {
                return user_response(400, message: 'Отчество обязательно для заполнения');
            }

            if ($houseFeature->addSubscriber($mobile, $name, $patronymic)) {
                $subscribers = $houseFeature->getSubscribers('mobile', $mobile);

                if (count($subscribers) > 0) {
                    $houseFeature->modifySubscriber($subscribers[0]['subscriberId'], ['audJti' => $audJti]);
                }
            } else {
                return user_response(422, 'Не удалось зарегестрироваться');
            }
        }

        if ($subscribers && count($subscribers) > 0) {
            $subscriber = $subscribers[0];

            foreach ($subscriber['flats'] as $item)
                if ((int)$item['flatId'] == $flat->house_flat_id) {
                    return user_response(200, "У вас уже есть доступ к данной квартире");
                }

            if ($houseFeature->addSubscriber($subscriber["mobile"], flatId: $flat->house_flat_id)) {
                $house = AddressHouse::findById($flat->address_house_id, setting: setting()->nonNullable());

                $fio = $request->name;

                if (trim($request->patronymic) !== 'Отчество') {
                    $fio .= ' ' . $request->patronymic;
                }

                $result = $externalFeature->qr($house->house_uuid, $flat->flat, substr((string)$request->mobile, 1), $fio, connection_ip($request->getRequest()));

                if (is_string($result)) {
                    return user_response(400, message: $result);
                }

                if ($result) {
                    return user_response(200, "Квартира успешно привязана");
                }

                return user_response(400, message: 'Не удалось полностью выполнить запрос, пользователь не привязан полностью');
            }

            return user_response(422, message: 'Неудалось добавиться в квартиру');
        }

        return user_response(404, message: 'Абонент не найден');
    }
}