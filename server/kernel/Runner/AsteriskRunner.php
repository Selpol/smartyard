<?php

namespace Selpol\Kernel\Runner;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RedisException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Push\PushFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Service\DeviceService;
use Selpol\Service\RedisService;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

class AsteriskRunner implements KernelRunner
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = logger('asterisk');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws ValidatorException
     */
    function __invoke(Kernel $kernel): int
    {
        $asterisk = config('asterisk');

        $ip = $_SERVER['REMOTE_ADDR'];

        $trust = false;

        foreach ($asterisk['trust'] as $range)
            if (ip_in_range($ip, $range)) {
                $trust = true;

                break;
            }

        if (!$trust) {
            header('Content-Type: application/json');

            echo '{"code":404,"name":"Not Found","message":"Не найдено"}';

            return 0;
        }

        $path = $this->getPath();

        $this->logger->debug('Request', ['path' => $path, 'ip' => $ip]);

        switch ($path[0]) {
            case 'aors':
            case 'auths':
            case 'endpoints':
                if (@$_POST['id'])
                    echo $this->response($this->getExtension($kernel, $_POST['id'], $path[0]));

                break;
            case 'extensions':
                $params = json_decode(file_get_contents("php://input"), true);

                if (is_array($params))
                    ksort($params);

                switch ($path[1]) {
                    case "autoopen":
                        $households = container(HouseFeature::class);

                        $flat = $households->getFlat(intval($params));

                        $rabbit = (int)$flat["whiteRabbit"];
                        $result = $flat["autoOpen"] > time() || ($rabbit && $flat["lastOpened"] + $rabbit * 60 > time());

                        echo json_encode($result);

                        $this->logger->debug('Get auto open', ['result' => $result, 'params' => $params]);

                        break;

                    case "flat":
                        $households = container(HouseFeature::class);

                        $flat = $households->getFlat(intval($params));

                        echo json_encode($flat);

                        $this->logger->debug('Get flat', ['flat' => $flat, 'params' => $params]);

                        break;

                    case "flatIdByPrefix":
                        $households = container(HouseFeature::class);

                        $apartment = $households->getFlats("flatIdByPrefix", $params);

                        echo json_encode($apartment);

                        $this->logger->debug('Get apartment', ['apartment' => $apartment, 'params' => $params]);

                        break;

                    case "apartment":
                        $households = container(HouseFeature::class);

                        $apartment = $households->getFlats("apartment", $params);

                        echo json_encode($apartment);

                        $this->logger->debug('Get apartment', ['apartment' => $apartment, 'params' => $params]);

                        break;

                    case "subscribers":
                        $households = container(HouseFeature::class);

                        $flat = $households->getSubscribers("flatId", intval($params));

                        echo json_encode($flat);

                        $this->logger->debug('Get flat', ['flat' => $flat, 'params' => $params]);

                        break;

                    case "domophone":
                        $households = container(HouseFeature::class);

                        $domophone = $households->getDomophone(intval($params));

                        echo json_encode($domophone);

                        $this->logger->debug('Get domophone', ['domophone' => $domophone, 'params' => $params]);

                        break;

                    case "entrance":
                        $households = container(HouseFeature::class);

                        $entrances = $households->getEntrances("domophoneId", ["domophoneId" => intval($params), "output" => "0"]);

                        if ($entrances) {
                            echo json_encode($entrances[0]);
                        } else {
                            echo json_encode(false);
                        }

                        $this->logger->debug('Get entrance', ['entrances' => $entrances, 'params' => $params]);

                        break;

                    case "camshot":
                        $redis = $kernel->getContainer()->get(RedisService::class)->getConnection();

                        if ($params["domophoneId"] >= 0) {
                            $households = container(HouseFeature::class);

                            $entrances = $households->getEntrances("domophoneId", ["domophoneId" => $params["domophoneId"], "output" => "0"]);

                            if ($entrances && $entrances[0]) {
                                $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);

                                if ($cameras && $cameras[0]) {
                                    $model = container(DeviceService::class)->camera($cameras[0]["model"], $cameras[0]["url"], $cameras[0]["credentials"]);

                                    $redis->setex("shot_" . $params["hash"], 3 * 60, $model->getScreenshot()->getContents());
                                    $redis->setex("live_" . $params["hash"], 3 * 60, json_encode([
                                        "model" => $cameras[0]["model"],
                                        "url" => $cameras[0]["url"],
                                        "credentials" => $cameras[0]["credentials"],
                                    ]));

                                    echo $params["hash"];

                                    $this->logger->debug('camshot()', ['shot' => "shot_" . $params["hash"]]);
                                }
                            }
                        }

                        break;

                    case "push":
                        $server = container(SipFeature::class)->server('extension', $params['extension']);

                        $params = [
                            "token" => $params["token"],
                            "type" => $params["tokenType"],
                            "hash" => $params["hash"],
                            "extension" => $params["extension"],
                            "server" => $server["ip"],
                            "port" => @$server["sip_tcp_port"] ?: 5060,
                            "transport" => "tcp",
                            "dtmf" => $params["dtmf"],
                            "timestamp" => time(),
                            "ttl" => 30,
                            "platform" => (int)$params["platform"] ? "ios" : "android",
                            "callerId" => $params["callerId"],
                            'domophoneId' => $params['domophoneId'],
                            "flatId" => $params["flatId"],
                            "flatNumber" => $params["flatNumber"],
                            "title" => 'Входящий вызов',
                        ];

                        $stun = container(SipFeature::class)->stun($params['extension']);

                        if ($stun) {
                            $params['stun'] = $stun;
                            $params['stunTransport'] = 'udp';
                        }

                        $this->logger->debug('Send push', ['push' => $params]);

                        container(PushFeature::class)->push($params);

                        break;
                    default:
                        break;
                }

                break;
            default:
                break;
        }

        return 0;
    }

    public function onFailed(Throwable $throwable, bool $fatal): int
    {
        if ($throwable instanceof ValidatorException)
            $this->logger->error($throwable->getValidatorMessage()->message, ['key' => $throwable->getValidatorMessage()->key, 'value' => $throwable->getValidatorMessage()->value]);
        else
            $this->logger->emergency($throwable, ['fatal' => $fatal]);

        return 0;
    }

    private function getPath(): array
    {
        $path = $_SERVER['REQUEST_URI'];

        $server = parse_url(config('api.asterisk'));

        if ($server && $server['path'])
            $path = substr($path, strlen($server['path']));

        if ($path && $path[0] == '/')
            $path = substr($path, 1);

        return explode('/', $path);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    private function getExtension(Kernel $kernel, string $extension, string $section): array
    {
        $redis = $kernel->getContainer()->get(RedisService::class)->getConnection();

        if ($extension[0] === '1' && strlen($extension) === 6) {
            $households = container(HouseFeature::class);

            $panel = $households->getDomophone((int)substr($extension, 1));

            switch ($section) {
                case 'aors':
                    if ($panel && $panel['credentials'])
                        return ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'];

                    break;

                case 'auths':

                    if ($panel && $panel['credentials'])
                        return ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $panel['credentials']];
                    break;

                case 'endpoints':
                    if ($panel && $panel['credentials']) {
                        return [
                            "id" => $extension,
                            "auth" => $extension,
                            "outbound_auth" => $extension,
                            "aors" => $extension,
                            "callerid" => $extension,
                            "context" => "default",
                            "disallow" => "all",
                            "allow" => "alaw,h264",
                            "rtp_symmetric" => "no",
                            "force_rport" => "no",
                            "rewrite_contact" => "yes",
                            "timers" => "no",
                            "direct_media" => "no",
                            "allow_subscribe" => "yes",
                            "dtmf_mode" => "rfc4733",
                            "ice_support" => "no",
                        ];
                    }
                    break;
            }
        }

        // mobile extension
        if ($extension[0] === "2" && strlen($extension) === 10) {
            switch ($section) {
                case 'aors':
                    $cred = $redis->get('mobile_extension_' . $extension);

                    if ($cred)
                        return ["id" => $extension, "max_contacts" => "1", "remove_existing" => "yes"];

                    break;

                case 'auths':
                    $cred = $redis->get('mobile_extension_' . $extension);

                    if ($cred)
                        return ["id" => $extension, "username" => $extension, "auth_type" => "userpass", "password" => $cred];

                    break;

                case 'endpoints':
                    $cred = $redis->get('mobile_extension_' . $extension);

                    if ($cred) {
                        return [
                            "id" => $extension,
                            "auth" => $extension,
                            "outbound_auth" => $extension,
                            "aors" => $extension,
                            "callerid" => $extension,
                            "context" => "default",
                            "disallow" => "all",
                            "allow" => "alaw,h264",
                            "rtp_symmetric" => "yes",
                            "force_rport" => "yes",
                            "rewrite_contact" => "yes",
                            "timers" => "no",
                            "direct_media" => "no",
                            "allow_subscribe" => "yes",
                            "dtmf_mode" => "rfc4733",
                            "ice_support" => "yes",
                        ];
                    }

                    break;
            }
        }

        // sip extension
        if ($extension[0] === '4' && strlen($extension) === 10) {
            $households = container(HouseFeature::class);

            $flatId = (int)substr($extension, 1);
            $flat = $households->getFlat($flatId);

            if ($flat) {
                $cred = $flat['sipPassword'];

                switch ($section) {
                    case 'aors':
                        if ($cred)
                            return ["id" => $extension, "max_contacts" => "1", "remove_existing" => "yes"];

                        break;

                    case 'auths':
                        if ($cred)
                            return ["id" => $extension, "username" => $extension, "auth_type" => "userpass", "password" => $cred];

                        break;

                    case 'endpoints':
                        if ($cred) {
                            return [
                                "id" => $extension,
                                "auth" => $extension,
                                "outbound_auth" => $extension,
                                "aors" => $extension,
                                "callerid" => $extension,
                                "context" => "default",
                                "disallow" => "all",
                                "allow" => "alaw,h264",
                                "rtp_symmetric" => "yes",
                                "force_rport" => "yes",
                                "rewrite_contact" => "yes",
                                "timers" => "no",
                                "direct_media" => "no",
                                "allow_subscribe" => "yes",
                                "dtmf_mode" => "rfc4733",
                                "ice_support" => "no",
                            ];
                        }

                        break;
                }
            }
        }

        // webrtc extension
        if ($extension[0] === "7" && strlen($extension) === 10) {
            switch ($section) {
                case 'aors':
                    $cred = $redis->get("webrtc_" . md5($extension));

                    if ($cred)
                        return ["id" => $extension, "max_contacts" => "1", "remove_existing" => "yes"];

                    break;

                case 'auths':
                    $cred = $redis->get("webrtc_" . md5($extension));

                    if ($cred)
                        return ["id" => $extension, "username" => $extension, "auth_type" => "userpass", "password" => $cred];

                    break;

                case 'endpoints':
                    $cred = $redis->get("webrtc_" . md5($extension));

                    $user = container(UserFeature::class)->getUser((int)substr($extension, 1));

                    if ($user && $cred) {
                        return [
                            "id" => $extension,
                            "auth" => $extension,
                            "outbound_auth" => $extension,
                            "aors" => $extension,
                            "callerid" => $user["realName"],
                            "context" => "default",
                            "disallow" => "all",
                            "allow" => "alaw,h264",
                            "dtmf_mode" => "rfc4733",
                            "webrtc" => "yes",
                        ];
                    }

                    break;
            }
        }

        return [];
    }

    private function response(array $params): string
    {
        $result = '';

        foreach ($params as $key => $value)
            $result .= urldecode($key) . '=' . urldecode($value) . '&';

        return $result;
    }
}