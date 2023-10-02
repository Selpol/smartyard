<?php

namespace Selpol\Feature\Dvr\Internal;

use Selpol\Feature\Dvr\DvrFeature;

class InternalDvrFeature extends DvrFeature
{
    public function getDVRServerByStream(string $url): array
    {
        $dvr_servers = $this->getDVRServers();

        $url = parse_url($url);
        $scheme = $url["scheme"] ?: 'http';
        $port = array_key_exists('port', $url) ? (int)($url["port"]) : null;

        if (!$port && $scheme == 'http') $port = 80;
        if (!$port && $scheme == 'https') $port = 443;

        $result = ['type' => 'flussonic']; // result by default if server not found in dvr_servers settings

        foreach ($dvr_servers as $server) {
            $u = parse_url($server['url']);

            if (
                ($u['scheme'] == $scheme) &&
                (!@$u['user'] || @$u['user'] == @$url["user"]) &&
                (!@$u['pass'] || @$u['pass'] == @$url["pass"]) &&
                ($u['host'] == $url["host"]) &&
                (!@$u['port'] || $u['port'] == $port)
            ) {
                $result = $server;
                break;
            }
        }

        return $result;
    }

    public function getDVRTokenForCam(array $cam, ?int $subscriberId): string
    {
        $dvrServer = $this->getDVRServerByStream($cam['dvrStream']);

        $result = '';

        if ($dvrServer)
            $result = @$dvrServer['token'] ?: '';

        return $result;
    }

    public function getDVRServers(): array
    {
        return config('feature.dvr.servers');
    }

    public function getUrlOfRecord(array $cam, int $subscriberId, int $start, int $finish): string|bool
    {
        $dvr = $this->getDVRServerByStream($cam['dvrStream']);

        switch ($dvr['type']) {
            case 'nimble':
                $path = parse_url($cam['dvrStream'], PHP_URL_PATH);

                if ($path[0] == '/') $path = substr($path, 1);

                $stream = $path;
                $token = $dvr['management_token'];
                $host = $dvr['management_ip'];
                $port = $dvr['management_port'];

                $salt = rand(0, 1000000);
                $str2hash = $salt . "/" . $token;
                $md5raw = md5($str2hash, true);
                $base64hash = base64_encode($md5raw);

                return "http://$host:$port/manage/dvr/export_mp4/$stream?start=$start&end=$finish&salt=$salt&hash=$base64hash";
            case 'macroscop':
                $parsed_url = parse_url($cam['dvrStream']);

                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                $host = $parsed_url['host'] ?? '';
                $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                $user = $parsed_url['user'] ?? '';
                $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
                $pass = ($user || $pass) ? "$pass@" : '';
                $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';

                $token = $this->getDVRTokenForCam($cam, $subscriberId);
                if ($token !== '') {
                    $query = $query . "&$token";
                }

                date_default_timezone_set('UTC');

                $from_time = urlencode(date("d.m.Y H:i:s", $start));
                $to_time = urlencode(date("d.m.Y H:i:s", $finish));

                return "$scheme$user$pass$host$port/exportarchive$query&fromtime=$from_time&totime=$to_time";
            case 'trassir':
                $parsed_url = parse_url($cam['dvrStream']);

                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
                $pass = ($user || $pass) ? "$pass@" : '';

                $token = $this->getDVRTokenForCam($cam, $subscriberId);

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
                $flussonic_token = $this->getDVRTokenForCam($cam, $subscriberId);
                $from = $start;
                $duration = $finish - $start;

                return $cam['dvrStream'] . "/archive-$from-$duration.mp4?token=$flussonic_token";
        }
    }

    public function getUrlOfScreenshot(array $cam, int $time, string|bool $addTokenToUrl = false): bool|string
    {
        $prefix = $cam['dvrStream'];

        $dvr = container(DvrFeature::class)->getDVRServerByStream($prefix);
        $type = $dvr['type'];

        switch ($type) {
            case 'nimble':
                return "$prefix/dvr_thumbnail_$time.mp4";
            case 'macroscop':
                $parsed_url = parse_url($cam['dvrStream']);

                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                $host = $parsed_url['host'] ?? '';
                $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                $user = $parsed_url['user'] ?? '';
                $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
                $pass = ($user || $pass) ? "$pass@" : '';
                // $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';

                if (isset($dvr['token'])) {
                    $token = $dvr['token'];
                    $query = $query . "&$token";
                }

                date_default_timezone_set('UTC');
                $start_time = urlencode(date("d.m.Y H:i:s", $time));

                return "$scheme$user$pass$host$port/site$query&withcontenttype=true&mode=archive&starttime=$start_time&resolutionx=480&resolutiony=270&streamtype=mainvideo";
            case 'trassir':
                $parsed_url = parse_url($cam['dvrStream']);

                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                $host = $parsed_url['host'] ?? '';
                $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                $user = $parsed_url['user'] ?? '';
                $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
                $pass = ($user || $pass) ? "$pass@" : '';

                $token = $this->getDVRTokenForCam($cam, null);

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
                return "$prefix/$time-preview.mp4" . ($addTokenToUrl ? ("?token=" . $this->getDVRTokenForCam($cam, null)) : "");
        }

        return false;
    }
}