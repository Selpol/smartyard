<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Http\Response;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;
use Throwable;

class IntercomController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function intercom(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $body = $this->request->getParsedBody();

        $validate = validator($body, ['flatId' => rule()->id()]);

        $flat_id = $validate['flatId'];

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return $this->rbtResponse(403, message: 'Квартира не находится в списках абонента');

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flat_id) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        $households = container(HouseFeature::class);

        if (@$body['settings']) {
            $params = [];
            $settings = $body['settings'];

            if (@$settings['CMS'])
                $params["cmsEnabled"] = $settings['CMS'] ? 1 : 0;

            if (@$settings['autoOpen']) {
                $d = date('Y-m-d H:i:s', strtotime($settings['autoOpen']));
                $params['autoOpen'] = $d;
            }

            if (array_key_exists('whiteRabbit', $settings)) {
                $wr = (int)$settings['whiteRabbit'];

                if (!in_array($wr, [0, 1, 2, 3, 5, 7, 10]))
                    $wr = 0;

                $params['whiteRabbit'] = $wr;
            }

            $flat_plog = $households->getFlat($flat_id)['plog'];

            $disable_plog = null;

            if (@$settings['disablePlog'] && $flat_owner && $flat_plog != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN)
                $disable_plog = ($settings['disablePlog'] == true);

            $hidden_plog = null;

            if (@$settings['hiddenPlog'] && $flat_owner && $flat_plog != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN)
                $hidden_plog = ($settings['hiddenPlog'] == true);

            if ($disable_plog === true) $params['plog'] = PlogFeature::ACCESS_DENIED;
            else if ($disable_plog === false) {
                if ($hidden_plog === false) $params['plog'] = PlogFeature::ACCESS_ALL;
                else $params['plog'] = PlogFeature::ACCESS_OWNER_ONLY;
            } else if ($hidden_plog !== null && $flat_plog == PlogFeature::ACCESS_ALL || $flat_plog == PlogFeature::ACCESS_OWNER_ONLY)
                $params['plog'] = $hidden_plog ? PlogFeature::ACCESS_OWNER_ONLY : PlogFeature::ACCESS_ALL;

            $households->modifyFlat($flat_id, $params);

            if (@$settings['VoIP']) {
                $params = [];
                $params['voipEnabled'] = $settings['VoIP'] ? 1 : 0;
                $households->modifySubscriber($user['subscriberId'], $params);
            }

            task(new IntercomSyncFlatTask($validate['flatId'], false))->high()->dispatch();
        }

        $subscriber = $households->getSubscribers('id', $user['subscriberId'])[0];
        $flat = $households->getFlat($flat_id);

        $result = [];

        $result['allowDoorCode'] = true;
        $result['doorCode'] = @$flat['openCode'] ?: '00000'; // TODO: разобраться с тем, как работает отключение кода
        $result['CMS'] = (bool)(@$flat['cmsEnabled']);
        $result['VoIP'] = (bool)(@$subscriber['voipEnabled']);
        $result['autoOpen'] = date('Y-m-d H:i:s', $flat['autoOpen']);
        $result['whiteRabbit'] = strval($flat['whiteRabbit']);

        if ($flat_owner && $flat['plog'] != PlogFeature::ACCESS_RESTRICTED_BY_ADMIN) {
            $result['disablePlog'] = $flat['plog'] == PlogFeature::ACCESS_DENIED;
            $result['hiddenPlog'] = !($flat['plog'] == PlogFeature::ACCESS_ALL);
        }

        $frs = container(FrsFeature::class);

        if ($frs) {
            $frsDisabled = null;

            foreach ($flat['entrances'] as $entrance) {
                $e = $households->getEntrance($entrance['entranceId']);

                $vstream = container(CameraFeature::class)->getCamera($e['cameraId']);

                if (strlen($vstream["frs"]) > 1) {
                    $frsDisabled = false;

                    break;
                }
            }

            if ($frsDisabled != null)
                $result['FRSDisabled'] = $frsDisabled;
        }

        if ($result)
            return $this->rbtResponse(200, $result);

        return $this->rbtResponse(404, message: 'Ничего нет');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function openDoor(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($this->request->getParsedBody(), ['domophoneId' => rule()->id(), 'doorId' => rule()->int()->clamp(0)]);

        $households = container(HouseFeature::class);

        $blocked = true;

        foreach ($user['flats'] as $flat) {
            $flatDetail = $households->getFlat($flat['flatId']);
            if ($flatDetail['autoBlock'] || $flatDetail['adminBlock'])
                continue;

            foreach ($flatDetail['entrances'] as $entrance) {
                $domophoneId = intval($entrance['domophoneId']);
                $e = $households->getEntrance($entrance['entranceId']);
                $doorId = intval($e['domophoneOutput']);

                if ($validate['domophoneId'] == $domophoneId && $validate['doorId'] == $doorId && !$flatDetail['manualBlock']) {
                    $blocked = false;

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

                container(PlogFeature::class)->addDoorOpenDataById(time(), $validate['domophoneId'], PlogFeature::EVENT_OPENED_BY_APP, $validate['doorId'], $user['mobile']);
            } catch (Throwable $throwable) {
                logger('intercom')->error($throwable);

                return $this->rbtResponse(404, name: 'Ошибка', message: 'Домофон недоступен');
            }

            return $this->rbtResponse();
        }

        return $this->rbtResponse(404, name: 'Не найдено', message: 'Услуга недоступна (договор заблокирован либо не оплачен)');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resetCode(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($this->request->getParsedBody(), ['flatId' => rule()->id()]);

        $flat_id = $validate['flatId'];

        if (!$flat_id)
            return $this->rbtResponse(404, message: 'Квартира не найдена');

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return $this->rbtResponse(404, message: 'Квартира у абонента не найдена');

        $households = container(HouseFeature::class);

        $params = [];
        $params['openCode'] = '!';
        $households->modifyFlat($flat_id, $params);
        $flat = $households->getFlat($flat_id);

        task(new IntercomSyncFlatTask($validate['flatId'], false))->high()->dispatch();

        return $this->rbtResponse(200, ["code" => intval($flat['openCode'])]);
    }
}