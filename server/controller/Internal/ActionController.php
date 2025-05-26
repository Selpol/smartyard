<?php

namespace Selpol\Controller\Internal;

use Selpol\Controller\Request\Internal\ActionCallFinishedRequest;
use Selpol\Controller\Request\Internal\ActionMotionDetectionRequest;
use Selpol\Controller\Request\Internal\ActionOpenDoorRequest;
use Selpol\Controller\Request\Internal\ActionSetRabbitGatesRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Entity\EntitySetting;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Route\RouteController;
use Selpol\Service\DatabaseService;
use Selpol\Service\FrsService;

/**
 * Действия для syslog
 */
#[Controller('/internal/actions')]
readonly class ActionController extends RouteController
{
    /**
     * Звонок завершен
     */
    #[Post('/callFinished')]
    public function callFinished(ActionCallFinishedRequest $request, PlogFeature $plogFeature): Response
    {
        $plogFeature->addCallDoneData($request->date, $request->ip, $request->callId);

        return user_response();
    }

    /**
     * Детекция движения
     */
    #[Post('/motionDetection')]
    public function motionDetection(ActionMotionDetectionRequest $request, FrsService $frsService): Response
    {
        $deviceCamera = DeviceCamera::fetch(
            criteria()->equal('ip', $request->ip),
            (new EntitySetting())->columns(['camera_id', 'frs_server_id'])
        );

        if (!$deviceCamera instanceof DeviceCamera || !$deviceCamera->frs_server_id) {
            file_logger('motion')->debug('Motion detection not enabled', ['frs' => '-', 'ip' => $request->ip]);

            return user_response(400, message: 'Детектор движений не включен');
        }

        $frsServer = FrsServer::findById($deviceCamera->frs_server_id, setting: setting()->columns(['url'])->nonNullable());

        $payload = ["streamId" => $deviceCamera->camera_id, "start" => $request->motionActive ? 't' : 'f'];

        $frsService->request('POST', $frsServer->url . "/api/motionDetection", $payload);

        return user_response();
    }

    /**
     * Открытие двери
     */
    #[Post('/openDoor')]
    public function openDoor(ActionOpenDoorRequest $request, PlogFeature $plogFeature, FrsService $frsService, DatabaseService $databaseService): Response
    {
        switch ($request->event) {
            case PlogFeature::EVENT_OPENED_BY_KEY:
            case PlogFeature::EVENT_OPENED_BY_CODE:
                $plogFeature->addDoorOpenData($request->date, $request->ip, $request->event, $request->door, $request->detail);

                return user_response();

            case PlogFeature::EVENT_OPENED_GATES_BY_CALL:
                return user_response();

            case PlogFeature::EVENT_OPENED_BY_BUTTON:
                $result = $databaseService->get(
                    'SELECT frs_server_id, camera_id FROM cameras 
                        WHERE camera_id = (
                        SELECT camera_id FROM houses_domophones 
                        LEFT JOIN houses_entrances USING (house_domophone_id)
                        WHERE ip = :ip AND domophone_output = :door)',
                    ["ip" => $request->ip, "door" => $request->door]
                );

                if (count($result) > 0) {
                    $streamId = $result[0]['camera_id'];
                    $frsServerId = $result[0]['frs_server_id'];

                    if (isset($frsServerId) && $frsServerId) {
                        $frsServer = FrsServer::findById($frsServerId, setting: setting()->columns(['url'])->nonNullable());

                        $payload = ["streamId" => strval($streamId)];
                        $apiResponse = $frsService->request('POST', $frsServer->url . "/api/doorIsOpen", $payload);

                        return user_response(201, $apiResponse);
                    }
                }

                return response(204);
        }

        return response(204);
    }

    /**
     * Белый кролик
     */
    #[Post('/setRabbitGates')]
    public function setRabbitGates(ActionSetRabbitGatesRequest $request, DatabaseService $databaseService): Response
    {
        $query = "UPDATE houses_flats SET last_opened = :last_opened
        WHERE (flat = :flat OR house_flat_id = :house_flat_id) AND white_rabbit > 0 AND address_house_id = (
        SELECT address_house_id from houses_houses_entrances 
        WHERE prefix = :prefix AND house_entrance_id = (
        SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
        WHERE ip = :ip AND entrance_type = 'wicket'))";

        $result = $databaseService->modify(
            $query,
            ['ip' => $request->ip, 'flat' => $request->apartmentNumber, 'house_flat_id' => $request->apartmentId, 'prefix' => $request->prefix, 'last_opened' => $request->date]
        );

        return user_response(202, ['id' => $result]);
    }
}