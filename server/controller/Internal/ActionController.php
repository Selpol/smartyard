<?php

namespace Selpol\Controller\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Entity\EntitySetting;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Service\DatabaseService;
use Selpol\Service\FrsService;

#[Controller('/internal/actions')]
readonly class ActionController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/callFinished')]
    public function callFinished(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        if (!isset($body["date"], $body["ip"]))
            return user_response(400, message: 'Неверный формат данных');

        ["date" => $date, "ip" => $ip, "callId" => $callId] = $body;

        container(PlogFeature::class)->addCallDoneData($date, $ip, $callId);

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/motionDetection')]
    public function motionDetection(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        if (!isset($body["ip"], $body["motionActive"]))
            return user_response(400, message: 'Неверный формат данных');

        file_logger('internal')->debug('Motion detection', $body);

        $logger = file_logger('motion');

        ["ip" => $ip, "motionActive" => $motionActive] = $body;

        $deviceCamera = DeviceCamera::fetch(
            criteria()->simple('frs', '!=', '-')->equal('ip', $ip),
            (new EntitySetting())->columns(['camera_id', 'frs'])
        );

        if (!$deviceCamera) {
            $logger->debug('Motion detection not enabled', ['frs' => '-', 'ip' => $ip]);

            return user_response(400, message: 'Детектор движений не включен');
        }

        $payload = ["streamId" => $deviceCamera->camera_id, "start" => $motionActive ? 't' : 'f'];

        container(FrsService::class)->request('POST', $deviceCamera->frs . "/api/motionDetection", $payload);

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/openDoor')]
    public function openDoor(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        if (!isset($body["date"], $body["ip"], $body["event"], $body["door"], $body["detail"])) return user_response(400);

        ["date" => $date, "ip" => $ip, "event" => $event, "door" => $door, "detail" => $detail] = $body;

        if (!isset($date, $ip, $event, $door, $detail)) return user_response(400, message: 'Неверный формат данных');

        $plog = container(PlogFeature::class);

        file_logger('internal')->debug('Open door request', $body);

        switch ($event) {
            case PlogFeature::EVENT_OPENED_BY_KEY:
            case PlogFeature::EVENT_OPENED_BY_CODE:
                $plog->addDoorOpenData($date, $ip, intval($event), intval($door), $detail);

                return user_response();

            case PlogFeature::EVENT_OPENED_GATES_BY_CALL:
                return user_response();

            case PlogFeature::EVENT_OPENED_BY_BUTTON:
                $db = container(DatabaseService::class);

                [0 => ["camera_id" => $streamId, "frs" => $frsUrl]] = $db->get(
                    'SELECT frs, camera_id FROM cameras 
                        WHERE camera_id = (
                        SELECT camera_id FROM houses_domophones 
                        LEFT JOIN houses_entrances USING (house_domophone_id)
                        WHERE ip = :ip AND domophone_output = :door)',
                    ["ip" => $ip, "door" => $door]
                );

                if (isset($frsUrl)) {
                    $payload = ["streamId" => strval($streamId)];
                    $apiResponse = container(FrsService::class)->request('POST', $frsUrl . "/api/doorIsOpen", $payload);

                    return user_response(201, $apiResponse);
                }

                return response(204);
        }

        return response(204);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/setRabbitGates')]
    public function setRabbitGates(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        if (!isset($body["ip"], $body["prefix"], $body["apartmentNumber"], $body["apartmentId"], $body["date"]))
            return user_response(400, message: 'Неверный формат данных');

        ["ip" => $ip, "prefix" => $prefix, "apartmentNumber" => $apartment_number, "apartmentId" => $apartment_id, "date" => $date] = $body;

        $query = "UPDATE houses_flats SET last_opened = :last_opened
        WHERE (flat = :flat OR house_flat_id = :house_flat_id) AND white_rabbit > 0 AND address_house_id = (
        SELECT address_house_id from houses_houses_entrances 
        WHERE prefix = :prefix AND house_entrance_id = (
        SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
        WHERE ip = :ip AND entrance_type = 'wicket'))";

        $result = container(DatabaseService::class)->modify(
            $query,
            ['ip' => $ip, 'flat' => $apartment_number, 'house_flat_id' => $apartment_id, 'prefix' => $prefix, 'last_opened' => $date]
        );

        return user_response(202, ['id' => $result]);
    }
}