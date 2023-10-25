<?php

namespace Selpol\Runner;

use RedisException;
use Selpol\Entity\Repository\Sip\SipUserRepository;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Push\PushFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Service\DeviceService;
use Selpol\Service\RedisService;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

class AsteriskRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    private const CYR = ['Љ', 'Њ', 'Џ', 'џ', 'ш', 'ђ', 'ч', 'ћ', 'ж', 'љ', 'њ', 'Ш', 'Ђ', 'Ч', 'Ћ', 'Ж', 'Ц', 'ц', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'];
    private const LAN = ['Lj', 'Nj', 'Dž', 'dž', 'š', 'đ', 'č', 'ć', 'ž', 'lj', 'nj', 'Š', 'Đ', 'Č', 'Ć', 'Ž', 'C', 'c', 'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'i', 'y', 'e', 'yu', 'ya', 'A', 'B', 'V', 'G', 'D', 'E', 'Io', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'I', 'Y', 'e', 'Yu', 'Ya'];

    /**
     * @throws RedisException
     * @throws ValidatorException
     */
    function run(array $arguments): int
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
                    echo $this->response($this->getExtension($_POST['id'], $path[0]));

                break;
            case 'extensions':
                $params = json_decode(file_get_contents('php://input'), true);

                if (is_array($params))
                    ksort($params);

                switch ($path[1]) {
                    case 'autoopen':
                        $households = container(HouseFeature::class);

                        $flat = $households->getFlat(intval($params));

                        $rabbit = (int)$flat['whiteRabbit'];
                        $result = $flat['autoOpen'] > time() || ($rabbit && $flat['lastOpened'] + $rabbit * 60 > time());

                        echo json_encode($result);

                        $this->logger->debug('Get auto open', ['result' => $result, 'params' => $params]);

                        break;

                    case 'flat':
                        $households = container(HouseFeature::class);

                        $flat = $households->getFlat(intval($params));

                        if (!$flat['autoBlock'] && !$flat['adminBlock'] && !$flat['manualBlock'])
                            echo json_encode($flat);

                        $this->logger->debug('Get flat', ['flat' => $flat, 'params' => $params]);

                        break;

                    case 'flatIdByPrefix':
                        $households = container(HouseFeature::class);

                        $apartments = array_filter($households->getFlats('flatIdByPrefix', $params), static fn(array $flat) => !$flat['autoBlock'] && !$flat['adminBlock'] && !$flat['manualBlock']);

                        if (count($apartments) > 0)
                            echo json_encode($apartments);

                        $this->logger->debug('Get apartments', ['apartments' => $apartments, 'params' => $params]);

                        break;

                    case 'apartment':
                        $households = container(HouseFeature::class);

                        $apartments = array_filter($households->getFlats('apartment', $params), static fn(array $flat) => !$flat['autoBlock'] && !$flat['adminBlock'] && !$flat['manualBlock']);

                        if (count($apartments) > 0)
                            echo json_encode($apartments);

                        $this->logger->debug('Get apartment', ['apartment' => $apartments, 'params' => $params]);

                        break;

                    case 'subscribers':
                        $households = container(HouseFeature::class);

                        $subscribers = array_filter($households->getSubscribers('flatId', intval($params)), static fn(array $subscriber) => !$subscriber['adminBlock'] && !$subscriber['manualBlock']);

                        if (count($subscribers) > 0)
                            echo json_encode($subscribers);

                        $this->logger->debug('Get flat', ['flat' => $subscribers, 'params' => $params]);

                        break;

                    case 'domophone':
                        $households = container(HouseFeature::class);

                        $domophone = $households->getDomophone(intval($params));

                        echo json_encode($domophone);

                        $this->logger->debug('Get domophone', ['domophone' => $domophone, 'params' => $params]);

                        break;

                    case 'entrance':
                        $households = container(HouseFeature::class);

                        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => intval($params), 'output' => '0']);

                        if ($entrances) {
                            echo json_encode($entrances[0]);
                        } else {
                            echo json_encode(false);
                        }

                        $this->logger->debug('Get entrance', ['entrances' => $entrances, 'params' => $params]);

                        break;

                    case 'camshot':
                        $redis = container(RedisService::class)->getConnection();

                        if ($params['domophoneId'] >= 0) {
                            $households = container(HouseFeature::class);

                            $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $params['domophoneId'], 'output' => '0']);

                            if ($entrances && $entrances[0]) {
                                $cameras = $households->getCameras('id', $entrances[0]['cameraId']);

                                if ($cameras && $cameras[0]) {
                                    $model = container(DeviceService::class)->camera($cameras[0]['model'], $cameras[0]['url'], $cameras[0]['credentials']);

                                    $redis->setex('shot_' . $params["hash"], 3 * 60, $model->getScreenshot()->getContents());
                                    $redis->setex('live_' . $params["hash"], 3 * 60, json_encode([
                                        'model' => $cameras[0]["model"],
                                        'url' => $cameras[0]["url"],
                                        'credentials' => $cameras[0]["credentials"],
                                    ]));

                                    echo $params['hash'];

                                    $this->logger->debug('camshot()', ['shot' => "shot_" . $params['hash']]);
                                }
                            }
                        }

                        break;

                    case 'push':
                        $server = container(SipFeature::class)->server('extension', $params['extension'])[0];

                        $params = [
                            'token' => $params['token'],
                            'type' => $params['tokenType'],
                            'hash' => $params['hash'],
                            'extension' => $params['extension'],
                            'server' => $server->external_ip,
                            'port' => 5060,
                            'transport' => 'tcp',
                            'dtmf' => $params['dtmf'],
                            'timestamp' => time(),
                            'ttl' => 30,
                            'platform' => (int)$params['platform'] ? 'ios' : 'android',
                            'callerId' => $params['callerId'] ?: 'WebRTC',
                            'domophoneId' => $params['domophoneId'],
                            'flatId' => $params['flatId'],
                            'flatNumber' => $params['flatNumber'],
                            'title' => 'Входящий вызов',
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

    public function error(Throwable $throwable): int
    {
        $this->logger->emergency($throwable);

        return 0;
    }

    private function getPath(): array
    {
        $path = $_SERVER['REQUEST_URI'];

        $server = parse_url(config_get('api.asterisk'));

        if ($server && $server['path'])
            $path = substr($path, strlen($server['path']));

        if ($path && $path[0] == '/')
            $path = substr($path, 1);

        return explode('/', $path);
    }

    /**
     * @throws RedisException
     */
    private function getExtension(string $extension, string $section): array
    {
        $redis = container(RedisService::class)->getConnection();

        if ($extension[0] === '1' && strlen($extension) === 6) {
            $households = container(HouseFeature::class);

            $panel = $households->getDomophone((int)substr($extension, 1));

            if ($panel && $panel['credentials']) {
                switch ($section) {
                    case 'aors':
                        return ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'];

                    case 'auths':
                        return ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $panel['credentials']];

                    case 'endpoints':
                        return [
                            'id' => $extension,
                            'auth' => $extension,
                            'outbound_auth' => $extension,
                            'aors' => $extension,
                            'callerid' => $extension,
                            'context' => 'default',
                            'disallow' => 'all',
                            'allow' => 'alaw,h264',
                            'rtp_symmetric' => 'no',
                            'force_rport' => 'no',
                            'rewrite_contact' => 'yes',
                            'timers' => 'no',
                            'direct_media' => 'no',
                            'allow_subscribe' => 'yes',
                            'dtmf_mode' => 'rfc4733',
                            'ice_support' => 'no',
                            'sos_number' => $panel['sosNumber'] ?? env('SOS_NUMBER', '112')
                        ];
                }
            }
        }

        // mobile extension
        if ($extension[0] === '2' && strlen($extension) === 10) {
            $cred = $redis->get('mobile_extension_' . $extension);

            if ($cred) {
                switch ($section) {
                    case 'aors':
                        return ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'];

                    case 'auths':
                        return ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $cred];

                    case 'endpoints':
                        return [
                            'id' => $extension,
                            'auth' => $extension,
                            'outbound_auth' => $extension,
                            'aors' => $extension,
                            'callerid' => $extension,
                            'context' => 'default',
                            'disallow' => 'all',
                            'allow' => 'alaw,h264',
                            'rtp_symmetric' => 'yes',
                            'force_rport' => 'yes',
                            'rewrite_contact' => 'yes',
                            'timers' => 'no',
                            'direct_media' => 'no',
                            'allow_subscribe' => 'yes',
                            'dtmf_mode' => 'rfc4733',
                            'ice_support' => 'yes',
                        ];
                }
            }
        }

        // sip extension
        if ($extension[0] === '4' && strlen($extension) === 10) {
            $households = container(HouseFeature::class);

            $flatId = (int)substr($extension, 1);
            $flat = $households->getFlat($flatId);

            if ($flat && $flat['sipPassword']) {
                switch ($section) {
                    case 'aors':
                        return ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'];

                    case 'auths':
                        return ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $flat['sipPassword']];

                    case 'endpoints':
                        return [
                            'id' => $extension,
                            'auth' => $extension,
                            'outbound_auth' => $extension,
                            'aors' => $extension,
                            'callerid' => $flat['flatId'],
                            'context' => 'default',
                            'disallow' => 'all',
                            'allow' => 'alaw,h264',
                            'rtp_symmetric' => 'yes',
                            'force_rport' => 'yes',
                            'rewrite_contact' => 'yes',
                            'timers' => 'no',
                            'direct_media' => 'yes',
                            'inband_progress' => 'yes',
                            'allow_subscribe' => 'yes',
                            'dtmf_mode' => 'rfc4733',
                            'ice_support' => 'no',
                        ];
                }
            }
        }

        // webrtc extension
        if ($extension[0] === '7' && strlen($extension) === 10) {
            $uid = intval(substr($extension, 1));
            $password = $redis->get('user:' . $uid . ':ws');

            if ($password) {
                switch ($section) {
                    case 'aors':
                        return ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'];

                    case 'auths':
                        return ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $password];

                    case 'endpoints':
                        $user = container(UserFeature::class)->getUser($uid);

                        if ($user) {
                            return [
                                'id' => $extension,
                                'auth' => $extension,
                                'outbound_auth' => $extension,
                                'aors' => $extension,
                                'callerid' => $this->transcript($user['realName']),
                                'context' => 'default',
                                'disallow' => 'all',
                                'allow' => 'alaw,h264',
                                'dtmf_mode' => 'rfc4733',
                                'webrtc' => 'yes',
                            ];
                        }

                        break;
                }
            }
        }

        if (strlen($extension) >= 6 && strlen($extension) <= 10) {
            try {
                $sipUserId = (int)substr($extension, 1);
                $sipUser = container(SipUserRepository::class)->findByIdAndType($sipUserId, (int)$extension[0]);

                switch ($section) {
                    case 'aors':
                        return ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'];

                    case 'auths':
                        return ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $sipUser->password];

                    case 'endpoints':
                        return [
                            'id' => $extension,
                            'auth' => $extension,
                            'outbound_auth' => $extension,
                            'aors' => $extension,
                            'callerid' => $this->transcript($sipUser->title),
                            'context' => 'default',
                            'disallow' => 'all',
                            'allow' => 'alaw,h264',
                            'rtp_symmetric' => 'yes',
                            'force_rport' => 'yes',
                            'rewrite_contact' => 'yes',
                            'timers' => 'no',
                            'direct_media' => 'no',
                            'allow_subscribe' => 'yes',
                            'dtmf_mode' => 'rfc4733',
                            'ice_support' => 'yes'
                        ];
                }
            } catch (Throwable) {

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

    public function transcript(string $value): string
    {
        return str_replace(self::CYR, self::LAN, $value);
    }
}