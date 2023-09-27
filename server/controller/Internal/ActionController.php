<?php

namespace Selpol\Controller\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Http\Response;
use Selpol\Service\DatabaseService;
use Selpol\Service\FrsService;

class ActionController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function callFinished(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset($body["date"], $body["ip"]))
            return $this->rbtResponse(400, message: 'Неверный формат данных');

        ["date" => $date, "ip" => $ip, "callId" => $callId] = $body;

        container(PlogFeature::class)->addCallDoneData($date, $ip, $callId);

        return $this->rbtResponse();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function motionDetection(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset($body["ip"], $body["motionActive"]))
            return $this->rbtResponse(400, message: 'Неверный формат данных');

        $db = container(DatabaseService::class);

        $logger = logger('motion');

        ["ip" => $ip, "motionActive" => $motionActive] = $body;

        $query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND ip = :ip';
        $params = ["ip" => $ip, "frs" => "-"];
        $result = $db->get($query, $params);

        if (!$result) {
            $logger->debug('Motion detection not enabled', ['frs' => '-', 'ip' => $ip]);

            return $this->rbtResponse(400, message: 'Детектор движений не включен');
        }

        [0 => ["camera_id" => $streamId, "frs" => $frsUrl]] = $result;

        $payload = ["streamId" => $streamId, "start" => $motionActive ? 't' : 'f'];

        container(FrsService::class)->request('POST', $frsUrl . "/api/motionDetection", $payload);

        return $this->rbtResponse();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function openDoor(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset($body["date"], $body["ip"], $body["event"], $body["door"], $body["detail"])) return $this->rbtResponse(400);

        ["date" => $date, "ip" => $ip, "event" => $event, "door" => $door, "detail" => $detail] = $body;

        if (!isset($date, $ip, $event, $door, $detail)) return $this->rbtResponse(400, message: 'Неверный формат данных');

        $plog = container(PlogFeature::class);

        logger('internal')->debug('Open door request', $body);

        switch ($event) {
            case PlogFeature::EVENT_OPENED_BY_KEY:
            case PlogFeature::EVENT_OPENED_BY_CODE:
                $plog->addDoorOpenData($date, $ip, intval($event), intval($door), $detail);

                return $this->rbtResponse();

            case PlogFeature::EVENT_OPENED_GATES_BY_CALL:
                return $this->rbtResponse();

            case PlogFeature::EVENT_OPENED_BY_BUTTON:
                $db = container(DatabaseService::class);

                [0 => [
                    "camera_id" => $streamId,
                    "frs" => $frsUrl
                ]] = $db->get(
                    'SELECT frs, camera_id FROM cameras 
                        WHERE camera_id = (
                        SELECT camera_id FROM houses_domophones 
                        LEFT JOIN houses_entrances USING (house_domophone_id)
                        WHERE ip = :ip AND domophone_output = :door)',
                    ["ip" => $ip, "door" => $door],
                    []
                );

                if (isset($frsUrl)) {
                    $payload = ["streamId" => strval($streamId)];
                    $apiResponse = container(FrsService::class)->request('POST', $frsUrl . "/api/doorIsOpen", $payload);

                    return $this->rbtResponse(201, $apiResponse);
                }

                return $this->response(204);
        }

        return $this->response(204);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function setRabbitGates(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset(
            $body["ip"],
            $body["prefix"],
            $body["apartmentNumber"],
            $body["apartmentId"],
            $body["date"],
        ))
            return $this->rbtResponse(400, message: 'Неверный формат данных');

        ["ip" => $ip, "prefix" => $prefix, "apartmentNumber" => $apartment_number, "apartmentId" => $apartment_id, "date" => $date,] = $body;

        $query = "UPDATE houses_flats SET last_opened = :last_opened
        WHERE (flat = :flat OR house_flat_id = :house_flat_id) AND white_rabbit > 0 AND address_house_id = (
        SELECT address_house_id from houses_houses_entrances 
        WHERE prefix = :prefix AND house_entrance_id = (
        SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
        WHERE ip = :ip AND entrance_type = 'wicket'))";
        $params = [
            "ip" => $ip,
            "flat" => $apartment_number,
            "house_flat_id" => $apartment_id,
            "prefix" => $prefix,
            "last_opened" => $date,
        ];

        $result = container(DatabaseService::class)->modify($query, $params);

        return $this->rbtResponse(202, ['id' => $result]);
    }

    public function getSyslogConfig(): Response
    {
        $config = config('feature.plog');

        return $this->rbtResponse(data: [
            'clickhouseService' => [
                'host' => $config['host'],
                'port' => $config['port'],
                'database' => $config['database'],
                'username' => $config['username'],
                'password' => $config['password'],
            ],
            'hw' => config('syslog_servers')
        ]);
    }
}