<?php

namespace Selpol\Runner;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\External\ExternalFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Selpol\Service\DeviceService;
use Selpol\Service\RedisService;
use Selpol\Framework\Validator\Exception\ValidatorException;
use Throwable;

class AsteriskRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerRunnerTrait;

    public function __construct()
    {
        $this->setLogger(file_logger('asterisk'));
    }

    /**
     * @throws ValidatorException
     */
    public function run(array $arguments): int
    {
        $path = $this->getPath();

        switch ($path[0]) {
            case 'aors':
            case 'auths':
            case 'endpoints':
                if (array_key_exists('id', $_POST)) {
                    echo $this->response($this->getExtension($_POST['id'], $path[0]));
                }

                break;
            case 'extensions':
                $params = json_decode(file_get_contents('php://input'), true);

                switch ($path[1]) {
                    case 'autoopen':
                        try {
                            $flat = HouseFlat::findById(intval($params), setting: setting()->columns(['auto_open', 'white_rabbit', 'last_opened']));

                            $result = ($flat->auto_open && $flat->auto_open > time()) || ($flat->white_rabbit && $flat->last_opened + $flat->white_rabbit * 60 > time());

                            echo json_encode($result);
                        } catch (Throwable) {
                            echo json_encode(false);
                        }

                        break;

                    case 'flat':
                        $households = container(HouseFeature::class);

                        $flat = $households->getFlat(intval($params));
                        $block = container(BlockFeature::class)->getFirstBlockForFlat(intval($params), [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL, BlockFeature::SUB_SERVICE_APP]);

                        if ($block == null) {
                            echo json_encode($flat);
                        }

                        break;

                    case 'flatIdByPrefix':
                        $households = container(HouseFeature::class);

                        $apartments = array_filter($households->getFlats('flatIdByPrefix', $params), static fn(array $flat): bool => container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]) == null);

                        if ($apartments !== []) {
                            echo json_encode($apartments);
                        }

                        break;

                    case 'apartment':
                        $households = container(HouseFeature::class);

                        $apartments = $households->getFlats('apartment', $params);
                        $filterApartments = array_filter($apartments, static fn(array $flat): bool => container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]) == null);

                        if ($filterApartments !== []) {
                            echo json_encode($filterApartments);
                        }

                        break;

                    case 'subscribers':
                        $households = container(HouseFeature::class);

                        $subscribers = array_filter($households->getSubscribers('flatId', intval($params)), static fn(array $subscriber): bool => container(BlockFeature::class)->getFirstBlockForSubscriber($subscriber['subscriberId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL, BlockFeature::SUB_SERVICE_APP]) == null);

                        echo json_encode($subscribers);

                        break;

                    case 'domophone':
                        $device = intercom(intval($params));

                        if (!$device) {
                            break;
                        }

                        echo json_encode([
                            'dtmf' => $device->resolver->string('sip.dtmf', '1'),
                            'sosNumber' => $device->resolver->string('sip.sos'),
                            'ip' => $device->intercom->ip
                        ]);

                        break;

                    case 'sos':
                        $device = intercom(intval($params));

                        if (!$device) {
                            echo json_encode(['sos_number' => env('SOS_NUMBER', '112')]);

                            break;
                        }

                        echo json_encode(['sos_number' => $device->resolver->string('sip.sos', '112')]);

                        break;

                    case 'entrance':
                        $households = container(HouseFeature::class);

                        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => intval($params), 'output' => '0']);

                        if ($entrances) {
                            echo json_encode($entrances[0]);
                        } else {
                            echo json_encode(false);
                        }

                        break;

                    case 'camshot':
                        $redis = container(RedisService::class);

                        if ($params['domophoneId'] >= 0) {
                            $households = container(HouseFeature::class);

                            $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $params['domophoneId'], 'output' => '0']);

                            if ($entrances && $entrances[0]) {
                                $camera = DeviceCamera::findById($entrances[0]['cameraId']);

                                if ($camera instanceof DeviceCamera) {
                                    $model = container(DeviceService::class)->cameraByEntity($camera);

                                    $redis->setEx('shot_' . $params["hash"], 3 * 60, $model->getScreenshot()->getContents());
                                    $redis->setEx('live_' . $params["hash"], 3 * 60, json_encode(['id' => $camera->camera_id, 'model' => $camera->model, 'url' => $camera->url, 'credentials' => $camera->credentials]));

                                    echo $params['hash'];
                                }
                            }
                        }

                        break;

                    case 'push':
                        $server = container(SipFeature::class)->server('extension', $params['extension'])[0];

                        $flat = HouseFlat::findById($params['flatId'], setting: setting()->columns(['address_house_id'])->nonNullable());
                        $house = AddressHouse::findById($flat->address_house_id, setting: setting()->columns(['house_full'])->nonNullable());

                        try {
                            $segments = explode(', ', $house->house_full);

                            if (str_starts_with($segments[0], 'г')) {
                                $address = implode(', ', array_slice($segments, 1));
                            } elseif (str_ends_with($segments[0], 'обл')) {
                                $address = implode(', ', array_slice($segments, 2));
                            } else {
                                $address = $house->house_full;
                            }
                        } catch (Throwable) {
                            $address = $house->house_full;
                        }

                        $params = [
                            'token' => $params['token'],
                            'type' => $params['tokenType'],
                            'hash' => $params['hash'],
                            'extension' => $params['extension'],
                            'server' => $server->external_ip,
                            'port' => $server->external_port,
                            'transport' => 'udp',
                            'dtmf' => $params['dtmf'],
                            'timestamp' => time(),
                            'ttl' => 30,
                            'platform' => (int) $params['platform'] !== 0 ? 'ios' : 'android',
                            'callerId' => $params['callerId'] ?: 'WebRTC',
                            'domophoneId' => $params['domophoneId'],
                            'flatId' => $params['flatId'],
                            'flatNumber' => $params['flatNumber'],
                            'voipEnabled' => $params['voipEnabled'] ?? false,
                            'title' => $address,
                        ];

                        $stun = container(SipFeature::class)->stun($params['extension']);

                        if ($stun) {
                            $params['stun'] = $stun;
                            $params['stunTransport'] = 'udp';
                        }

                        container(ExternalFeature::class)->push($params);

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

        $server = parse_url((string) config_get('api.asterisk'));

        if ($server && $server['path']) {
            $path = substr((string) $path, strlen($server['path']));
        }

        if ($path && $path[0] == '/') {
            $path = substr((string) $path, 1);
        }

        return explode('/', (string) $path);
    }

    private function getExtension(string $extension, string $section): array
    {
        if ($extension[0] === '1' && strlen($extension) === 6) {
            $intercom = DeviceIntercom::findById((int) substr($extension, 1), setting: setting()->columns(['credentials']));

            if ($intercom instanceof DeviceIntercom && $intercom->credentials) {
                return match ($section) {
                    'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'],
                    'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $intercom->credentials],
                    'endpoints' => [
                        'id' => $extension,
                        'auth' => $extension,
                        'outbound_auth' => $extension,
                        'aors' => $extension,
                        'callerid' => $extension,
                        'transport' => 'transport-udp-local',
                        'context' => 'default',
                        'disallow' => 'all',
                        'allow' => 'alaw,ulaw,h264',
                        'rtp_symmetric' => 'no',
                        'force_rport' => 'no',
                        'rewrite_contact' => 'yes',
                        'timers' => 'no',
                        'direct_media' => 'no',
                        'allow_subscribe' => 'yes',
                        'dtmf_mode' => 'rfc4733',
                        'ice_support' => 'no'
                    ],
                };
            }
        }

        // mobile extension
        if ($extension[0] === '2' && strlen($extension) === 10) {
            $cred = container(RedisService::class)->get('mobile_extension_' . $extension);

            if ($cred) {
                return match ($section) {
                    'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'],
                    'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $cred],
                    'endpoints' => [
                        'id' => $extension,
                        'auth' => $extension,
                        'outbound_auth' => $extension,
                        'aors' => $extension,
                        'callerid' => $extension,
                        'transport' => 'transport-udp',
                        'context' => 'default',
                        'disallow' => 'all',
                        'allow' => 'alaw,ulaw,h264',
                        'rtp_symmetric' => 'yes',
                        'force_rport' => 'yes',
                        'rewrite_contact' => 'yes',
                        'timers' => 'no',
                        'direct_media' => 'no',
                        'allow_subscribe' => 'yes',
                        'dtmf_mode' => 'rfc4733',
                        'ice_support' => 'yes',
                    ],
                };
            }
        }

        // sip extension
        if ($extension[0] === '4' && strlen($extension) === 10) {
            $flat = HouseFlat::findById((int) substr($extension, 1), setting: setting()->columns(['house_flat_id', 'sip_password']));

            if ($flat instanceof HouseFlat && $flat->sip_password) {
                return match ($section) {
                    'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'],
                    'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $flat->sip_password],
                    'endpoints' => [
                        'id' => $extension,
                        'auth' => $extension,
                        'outbound_auth' => $extension,
                        'aors' => $extension,
                        'callerid' => $flat->house_flat_id,
                        'transport' => 'transport-udp',
                        'context' => 'default',
                        'disallow' => 'all',
                        'allow' => 'alaw,ulaw,h264',
                        'rtp_symmetric' => 'yes',
                        'force_rport' => 'yes',
                        'rewrite_contact' => 'yes',
                        'timers' => 'no',
                        'direct_media' => 'yes',
                        'inband_progress' => 'yes',
                        'allow_subscribe' => 'yes',
                        'dtmf_mode' => 'rfc4733',
                        'ice_support' => 'no',
                    ],
                };
            }
        }

        // webrtc extension
        if ($extension[0] === '7' && strlen($extension) === 10) {
            $uid = intval(substr($extension, 1));
            $password = container(RedisService::class)->get('user:' . $uid . ':ws');

            if ($password) {
                return match ($section) {
                    'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'],
                    'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $password],
                    'endpoints' => [
                        'id' => $extension,
                        'auth' => $extension,
                        'outbound_auth' => $extension,
                        'aors' => $extension,
                        'callerid' => $uid,
                        'context' => 'default',
                        'disallow' => 'all',
                        'allow' => 'alaw,ulaw,h264',
                        'dtmf_mode' => 'rfc4733',
                        'webrtc' => 'yes',
                    ],
                };
            }
        }

        if (strlen($extension) >= 6 && strlen($extension) <= 10) {
            try {
                $sipUserId = (int) substr($extension, 1);
                $sipUser = SipUser::findById($sipUserId, criteria()->equal('type', (int) $extension[0]), setting()->columns(['title', 'password']));

                if ($sipUser) {
                    return match ($section) {
                        'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'],
                        'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $sipUser->password],
                        'endpoints' => [
                            'id' => $extension,
                            'auth' => $extension,
                            'outbound_auth' => $extension,
                            'aors' => $extension,
                            'callerid' => $sipUser->title,
                            'context' => 'default',
                            'disallow' => 'all',
                            'allow' => 'alaw,ulaw,h264',
                            'rtp_symmetric' => 'yes',
                            'force_rport' => 'yes',
                            'rewrite_contact' => 'yes',
                            'timers' => 'no',
                            'direct_media' => 'no',
                            'allow_subscribe' => 'yes',
                            'dtmf_mode' => 'rfc4733',
                            'ice_support' => 'yes'
                        ],
                    };
                }
            } catch (Throwable) {
            }
        }

        return [];
    }

    private function response(array $params): string
    {
        $result = '';

        foreach ($params as $key => $value) {
            $result .= urldecode($key) . '=' . urldecode((string) $value) . '&';
        }

        return $result;
    }
}
