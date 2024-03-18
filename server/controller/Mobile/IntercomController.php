<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;
use Throwable;

#[Controller('/mobile/address', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM]])]
readonly class IntercomController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Post(
        '/intercom',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId', 'role' => 0],
            BlockFlatMiddleware::class => ['flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function intercom(ServerRequestInterface $request, HouseFeature $houseFeature, CameraFeature $cameraFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $body = $request->getParsedBody();

        $validate = validator($body, ['flatId' => rule()->id()]);

        if (@$body['settings']) {
            $params = [];
            $settings = $body['settings'];

            if (array_key_exists('CMS', $settings))
                $params["cmsEnabled"] = $settings['CMS'] ? 1 : 0;

            if (array_key_exists('autoOpen', $settings)) {
                $d = date('Y-m-d H:i:s', strtotime($settings['autoOpen']));
                $params['autoOpen'] = $d;
            }

            if (array_key_exists('whiteRabbit', $settings)) {
                $wr = (int)$settings['whiteRabbit'];

                if (!in_array($wr, [0, 1, 2, 3, 5, 7, 10]))
                    $wr = 0;

                $params['whiteRabbit'] = $wr;
            }

            $flat_plog = $houseFeature->getFlat($validate['flatId'])['plog'];

            $disable_plog = null;

            if (@$settings['disablePlog'] && $flat_plog != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN)
                $disable_plog = ($settings['disablePlog'] == true);

            $hidden_plog = null;

            if (@$settings['hiddenPlog'] && $flat_plog != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN)
                $hidden_plog = ($settings['hiddenPlog'] == true);

            if ($disable_plog === true) $params['plog'] = PlogFeature::ACCESS_DENIED;
            else if ($disable_plog === false) {
                if ($hidden_plog === false) $params['plog'] = PlogFeature::ACCESS_ALL;
                else $params['plog'] = PlogFeature::ACCESS_OWNER_ONLY;
            } else if ($hidden_plog !== null && $flat_plog == PlogFeature::ACCESS_ALL || $flat_plog == PlogFeature::ACCESS_OWNER_ONLY)
                $params['plog'] = $hidden_plog ? PlogFeature::ACCESS_OWNER_ONLY : PlogFeature::ACCESS_ALL;

            if ($this->getUser()->getIdentifier() == 130)
                file_logger('intercom')->debug('', ['param' => $params, 'settings' => $settings]);

            $houseFeature->modifyFlat($validate['flatId'], $params);

            if (@$settings['VoIP']) {
                $params = [];
                $params['voipEnabled'] = $settings['VoIP'] ? 1 : 0;
                $houseFeature->modifySubscriber($user['subscriberId'], $params);
            }

            task(new IntercomSyncFlatTask(-1, $validate['flatId'], false))->high()->dispatch();
        }

        $subscriber = $houseFeature->getSubscribers('id', $user['subscriberId'])[0];
        $flat = $houseFeature->getFlat($validate['flatId']);

        $result = [];

        $result['allowDoorCode'] = true;
        $result['doorCode'] = @$flat['openCode'] ?: '00000';
        $result['CMS'] = (bool)(@$flat['cmsEnabled']);
        $result['VoIP'] = (bool)(@$subscriber['voipEnabled']);
        $result['autoOpen'] = date('Y-m-d H:i:s', $flat['autoOpen']);
        $result['whiteRabbit'] = strval($flat['whiteRabbit']);

        if ($flat['plog'] != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN) {
            $result['disablePlog'] = $flat['plog'] == PlogFeature::ACCESS_DENIED;
            $result['hiddenPlog'] = !($flat['plog'] == PlogFeature::ACCESS_ALL);
        }

        $frsDisabled = null;

        foreach ($flat['entrances'] as $entrance) {
            $e = $houseFeature->getEntrance($entrance['entranceId']);

            if ($e['cameraId']) {
                $vstream = $cameraFeature->getCamera($e['cameraId']);

                if (strlen($vstream["frs"]) > 1) {
                    $frsDisabled = false;

                    break;
                }
            }
        }

        if ($frsDisabled != null)
            $result['FRSDisabled'] = $frsDisabled;

        if ($result)
            return user_response(200, $result);

        return user_response(404, message: 'Ничего нет');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Post('/openDoor', includes: [BlockMiddleware::class => [BlockFeature::SUB_SERVICE_OPEN]])]
    public function openDoor(ServerRequestInterface $request, HouseFeature $houseFeature, PlogFeature $plogFeature, BlockFeature $blockFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($request->getParsedBody(), ['domophoneId' => rule()->id(), 'doorId' => rule()->int()->clamp(0)]);

        $blocked = true;
        $flatsId = [];

        foreach ($user['flats'] as $flat) {
            if ($blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_OPEN]) != null)
                continue;

            $flatDetail = $houseFeature->getFlat($flat['flatId']);

            foreach ($flatDetail['entrances'] as $entrance) {
                $domophoneId = intval($entrance['domophoneId']);
                $e = $houseFeature->getEntrance($entrance['entranceId']);
                $doorId = intval($e['domophoneOutput']);

                if ($validate['domophoneId'] == $domophoneId && $validate['doorId'] == $doorId) {
                    $blocked = false;

                    if ($e['entranceType'] === 'wicket')
                        $flatsId[] = $flat['flatId'];

                    break;
                }
            }

            if (!$blocked)
                break;
        }

        if (!$blocked) {
            try {
                $model = intercom($validate['domophoneId']);

                $model->open($validate['doorId'] ?: 0);

                foreach (array_unique($flatsId) as $flatId)
                    $houseFeature->doorOpened($flatId);

                $plogFeature->addDoorOpenDataById(time(), $validate['domophoneId'], PlogFeature::EVENT_OPENED_BY_APP, $validate['doorId'], $user['mobile']);
            } catch (Throwable $throwable) {
                file_logger('intercom')->error($throwable);

                return user_response(404, name: 'Ошибка', message: 'Домофон недоступен');
            }

            return user_response();
        }

        return user_response(403, name: 'Не найдено', message: 'Услуга недоступна (договор заблокирован, либо не оплачен)');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post(
        '/resetCode',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId'],
            BlockFlatMiddleware::class => ['flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function resetCode(ServerRequestInterface $request, HouseFeature $houseFeature): Response
    {
        $validate = validator($request->getParsedBody(), ['flatId' => rule()->id()]);

        $params = [];
        $params['openCode'] = '!';

        $houseFeature->modifyFlat($validate['flatId'], $params);

        $flat = $houseFeature->getFlat($validate['flatId']);

        task(new IntercomSyncFlatTask(-1, $validate['flatId'], false))->high()->dispatch();

        return user_response(200, ["code" => intval($flat['openCode'])]);
    }
}