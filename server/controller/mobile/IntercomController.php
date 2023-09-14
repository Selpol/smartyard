<?php

namespace Selpol\Controller\mobile;

use backends\plog\plog;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;
use Selpol\Validator\Rule;
use Throwable;

class IntercomController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function intercom(): Response
    {
        $user = $this->getSubscriber();

        $body = $this->request->getParsedBody();

        $validate = validator($body, ['flatId' => [Rule::id()]]);

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

        $households = backend("households");
        $plog = backend("plog");

        if (@$body['settings']) {
            $params = [];
            $settings = $body['settings'];

            if (@$settings['CMS'])
                $params["cmsEnabled"] = ($settings['CMS'] == 't') ? 1 : 0;

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

            if (@$settings['disablePlog'] && $flat_owner && $plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN)
                $disable_plog = ($settings['disablePlog'] == 't');

            $hidden_plog = null;

            if (@$settings['hiddenPlog'] && $flat_owner && $plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN)
                $hidden_plog = ($settings['hiddenPlog'] == 't');

            if ($disable_plog === true) $params['plog'] = plog::ACCESS_DENIED;
            else if ($disable_plog === false) {
                if ($hidden_plog === false) $params['plog'] = plog::ACCESS_ALL;
                else $params['plog'] = plog::ACCESS_OWNER_ONLY;
            } else if ($hidden_plog !== null && $flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY)
                $params['plog'] = $hidden_plog ? plog::ACCESS_OWNER_ONLY : plog::ACCESS_ALL;

            $households->modifyFlat($flat_id, $params);

            if (@$settings['VoIP']) {
                $params = [];
                $params['voipEnabled'] = ($settings['VoIP'] == 't') ? 1 : 0;
                $households->modifySubscriber($user['subscriberId'], $params);
            }

            dispatch_high(new IntercomSyncFlatTask($validate['flatId'], false));
        }

        $subscriber = $households->getSubscribers('id', $user['subscriberId'])[0];
        $flat = $households->getFlat($flat_id);

        $result = [];

        $result['allowDoorCode'] = 't';
        $result['doorCode'] = @$flat['openCode'] ?: '00000'; // TODO: разобраться с тем, как работает отключение кода
        $result['CMS'] = @$flat['cmsEnabled'] ? 't' : 'f';
        $result['VoIP'] = @$subscriber['voipEnabled'] ? 't' : 'f';
        $result['autoOpen'] = date('Y-m-d H:i:s', $flat['autoOpen']);
        $result['whiteRabbit'] = strval($flat['whiteRabbit']);

        if ($flat_owner && $plog && $flat['plog'] != plog::ACCESS_RESTRICTED_BY_ADMIN) {
            $result['disablePlog'] = $flat['plog'] == plog::ACCESS_DENIED ? 't' : 'f';
            $result['hiddenPlog'] = $flat['plog'] == plog::ACCESS_ALL ? 'f' : 't';
        }

        //check for FRS presence on at least one entrance of the flat
        $frs = backend("frs");

        if ($frs) {
            $cameras = backend("cameras");
            $frsDisabled = null;

            foreach ($flat['entrances'] as $entrance) {
                $e = $households->getEntrance($entrance['entranceId']);

                if ($cameras) {
                    $vstream = $cameras->getCamera($e['cameraId']);

                    if (strlen($vstream["frs"]) > 1) {
                        $frsDisabled = 'f';

                        break;
                    }
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
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), ['domophoneId' => [Rule::id()], 'doorId' => [Rule::id()]]);

        $households = backend("households");

        // Check intercom is blocking
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
            $households = backend("households");
            $domophone = $households->getDomophone($validate['domophoneId']);

            try {
                $model = intercom($domophone["model"], $domophone["url"], $domophone["credentials"]);

                $model->open($validate['doorId'] ?: 0);

                $plog = backend("plog");

                if ($plog)
                    $plog->addDoorOpenDataById(time(), $validate['domophoneId'], $plog::EVENT_OPENED_BY_APP, $validate['doorId'], $user['mobile']);
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
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), ['flatId' => [Rule::id()]]);

        $flat_id = $validate['flatId'];

        if (!$flat_id)
            return $this->rbtResponse(404, message: 'Квартира не найдена');

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return $this->rbtResponse(404, message: 'Квартира у абонента не найдена');

        $households = backend("households");

        $params = [];
        $params['openCode'] = '!';
        $households->modifyFlat($flat_id, $params);
        $flat = $households->getFlat($flat_id);

        dispatch_high(new IntercomSyncFlatTask($validate['flatId'], false));

        return $this->rbtResponse(200, ["code" => intval($flat['openCode'])]);
    }
}