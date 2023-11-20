<?php

namespace Selpol\Feature\Dvr\Internal;

use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Dvr\DvrFeature;

readonly class InternalDvrFeature extends DvrFeature
{
    public function getDVRServerByCamera(array $camera): ?DvrServer
    {
        if (array_key_exists('dvrServerId', $camera) && $camera['dvrServerId'])
            return DvrServer::findById($camera['dvrServerId']);

        return null;
    }

    public function getUrlForCamera(DvrServer $server, array $camera): string
    {
        if ($server->type === 'trassir')
            return $server->url . '/?channel=' . $camera['dvrStream'];

        return $server->url . '/' . $camera['dvrStream'];
    }

    public function getTokenForCamera(DvrServer $server, array $camera, ?int $subscriberId): string
    {
        if ($server->type === 'flussonic') {
            $startTime = time() - 300;
            $endTime = $startTime + 3600 * 3;

            $salt = bin2hex(openssl_random_pseudo_bytes(16));
            $hash = sha1($camera['dvrStream'] . 'no_check_ip' . $startTime . $endTime . $server->token . $salt);

            return $hash . '-' . $salt . '-' . $endTime . '-' . $startTime;
        }

        return $server->token;
    }

    public function getDVRServers(): array
    {
        return DvrServer::fetchAll();
    }

    public function getUrlOfRecord(array $cam, int $subscriberId, int $start, int $finish): string|bool
    {
        $dvr = $this->getDVRServerByCamera($cam);

        switch ($dvr?->type ?? 'flussonic') {
            case 'trassir':
                $parsed_url = parse_url($this->getUrlForCamera($dvr, $cam));

                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
                $pass = ($user || $pass) ? "$pass@" : '';

                $token = $this->getTokenForCamera($dvr, $cam, $subscriberId);

                $guid = false;
                if (isset($parsed_url['query'])) {
                    parse_str($parsed_url['query'], $parsed_query);
                    $guid = isset($parsed_query['channel']) ? $parsed_query['channel'] : '';
                }
                date_default_timezone_set('UTC');

                $request_url = "$scheme$user$pass$host$port/login?$token";
                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );
                $sid_response = json_decode(file_get_contents($request_url, false, stream_context_create($arrContextOptions)), true);
                var_dump($sid_response);
                $sid = @$sid_response["sid"] ?: false;
                if (!$sid || !$guid) return false;

                $url = "$scheme$user$pass$host$port/jit-export-create-task?sid=$sid";
                $payload = [
                    "resource_guid" => $guid, // GUID Канала
                    "start_ts" => $start * 1000000,
                    "end_ts" => $finish * 1000000,
                    "is_hardware" => 0,
                    "prefer_substream" => 0
                ];
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($payload) {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: appplication/json'
                    ));

                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
                }
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                var_dump($url);
                var_dump($payload);
                $task_id_response = json_decode(curl_exec($curl), true);
                var_dump($task_id_response);
                curl_close($curl);
                $success = @$task_id_response["success"] ?: false;
                $task_id = @$task_id_response["task_id"] ?: false;
                if ($success != 1 || !$task_id) return false;

                $url = "$scheme$user$pass$host$port/jit-export-task-status?sid=$sid";

                $payload = [
                    "task_id" => $task_id
                ];

                $active = false;
                $attempts_count = 30;

                while (!$active && $attempts_count > 0) {
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_POST, 1);
                    if ($payload) {
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                            'Content-Type: appplication/json'
                        ));

                        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
                    }
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

                    var_dump($url);
                    var_dump($payload);
                    $task_id_response = json_decode(curl_exec($curl), true);
                    var_dump($task_id_response);
                    curl_close($curl);
                    $success = @$task_id_response["success"] ?: false;
                    $active = @$task_id_response["active"] ?: false;
                    if ($success == 1 || $active) break;
                    sleep(2);
                    $attempts_count = $attempts_count - 1;
                }

                if (!$active) return false;

                return "$scheme$user$pass$host$port/jit-export-download?sid=$sid&task_id=$task_id";
            default:
                $flussonic_token = $this->getTokenForCamera($dvr, $cam, $subscriberId);
                $from = $start;
                $duration = $finish - $start;

                return $this->getUrlForCamera($dvr, $cam) . "/archive-$from-$duration.mp4?token=$flussonic_token";
        }
    }

    public function getUrlOfScreenshot(array $cam, int $time, string|bool $addTokenToUrl = false): bool|string
    {
        $dvr = container(DvrFeature::class)->getDVRServerByCamera($cam);

        switch ($dvr?->type ?? 'flussonic') {
            case 'trassir':
                $parsed_url = parse_url($this->getUrlForCamera($dvr, $cam));

                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                $host = $parsed_url['host'] ?? '';
                $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                $user = $parsed_url['user'] ?? '';
                $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
                $pass = ($user || $pass) ? "$pass@" : '';

                $token = $this->getTokenForCamera($dvr, $cam, null);

                $guid = false;
                if (isset($parsed_url['query'])) {
                    parse_str($parsed_url['query'], $parsed_query);
                    $guid = $parsed_query['channel'] ?? '';
                }

                date_default_timezone_set('UTC');

                $request_url = "$scheme$user$pass$host$port/login?$token";
                $arrContextOptions = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));
                $sid_response = json_decode(file_get_contents($request_url, false, stream_context_create($arrContextOptions)), true);
                $sid = @$sid_response["sid"] ?: false;

                if (!$sid || !$guid) break;

                $timestamp = urlencode(date("Y-m-d H:i:s", $time));

                return "$scheme$user$pass$host$port/screenshot/$guid?timestamp=$timestamp&sid=$sid";
            default:
                return $this->getUrlForCamera($dvr, $cam) . "/$time-preview.mp4" . ($addTokenToUrl ? ("?token=" . $this->getTokenForCamera($dvr, $cam, null)) : "");
        }

        return false;
    }
}