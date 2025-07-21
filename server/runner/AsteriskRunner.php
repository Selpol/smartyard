<?php

namespace Selpol\Runner;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\External\ExternalFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Selpol\Service\DatabaseService;
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
                    case 'call':
                        try {
                            $device = intercom(intval($params['domophone_id']));
                            $entrance = $device->intercom->entrances()->fetchAll(criteria()->equal('domophone_output', 0))[0];

                            if (strlen($params['extension']) < 5) {
                                $id = intval($params['extension']);
                                $flats = $entrance->flats()->fetchAll(criteria()->equal('flat', $id));

                                if (count($flats) == 0) {
                                    echo json_encode(['success' => false, 'message' => 'Не удалось найти квартиру ' . $id]);

                                    break;
                                }

                                $flat = $flats[0];
                            } else if (strlen($params['extension']) == 10 && str_starts_with($params['extension'], '1')) {
                                $id = intval(substr($params['extension'], 1));
                                $flat = HouseFlat::findById($id);

                                if ($flat == null) {
                                    echo json_encode(['success' => false, 'message' => 'Не удалось найти квартиру ' . $id]);

                                    break;
                                }
                            } else {
                                $number = intval(substr($params['extension'], 4));
                                $prefix = intval(substr($params['extension'], 0, 4));

                                $statement = container(DatabaseService::class)->statement('select house_flat_id from houses_entrances_flats where house_flat_id in (select house_flat_id from houses_flats where address_house_id in (select address_house_id from houses_houses_entrances where house_entrance_id in (select house_entrance_id from houses_entrances where house_domophone_id = :house_domophone_id) and prefix = :prefix)) and apartment = :apartment group by house_flat_id');
                                $statement->execute(['house_domophone_id' => $device->intercom->house_domophone_id, 'prefix' => $prefix, 'apartment' => $number]);

                                $flat = HouseFlat::findById($statement->fetchColumn(0));

                                if ($flat == null) {
                                    echo json_encode(['success' => false, 'message' => 'Не удалось найти квартиру номер ' . $number . ' префикс ' . $prefix]);

                                    break;
                                }
                            }

                            if (container(BlockFeature::class)->getFirstBlockForFlat($flat->house_flat_id, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL])) {
                                echo json_encode(['success' => false]);

                                break;
                            }

                            if (($flat->auto_open && $flat->auto_open > time()) || ($flat->white_rabbit && $flat->last_opened + $flat->white_rabbit * 60 > time())) {
                                echo json_encode([
                                    'success' => true,
                                    'data' => [
                                        'auto_open' => ($flat->auto_open && $flat->auto_open > time()) || ($flat->white_rabbit && $flat->last_opened + $flat->white_rabbit * 60 > time()),
                                        'dtmf' => $device->resolver->int(ConfigKey::SipDtmf, 1),
                                    ]
                                ]);

                                break;
                            }

                            $cmsConnected = $entrance->cmses()->hasMany(criteria()->equal('apartment', $flat->flat)) > 0;
                            $hasCms = false;

                            if (!$cmsConnected) {
                                $flatEntrances = $flat->entrances()->fetchAll();

                                foreach ($flatEntrances as $flatEntrance) {
                                    if ($flatEntrance->cmses()->hasMany(criteria()->equal('apartment', $flat->flat)) > 0) {
                                        $hasCms = true;

                                        break;
                                    }
                                }
                            }

                            $subscribers = $flat->subscribers()->fetchAll(relationCriteria: criteria()->equal('call', 1));

                            $subscribers = array_filter($subscribers, static function (HouseSubscriber $subscriber) {
                                if (is_null($subscriber->platform)) {
                                    return false;
                                }

                                if (is_null($subscriber->push_token)) {
                                    return false;
                                }

                                if (is_null($subscriber->push_token_type)) {
                                    return false;
                                }

                                return true;
                            });

                            $blocks = SubscriberBlock::fetchAll(
                                criteria()
                                    ->in('subscriber_id', array_map(static fn(HouseSubscriber $subscriber) => $subscriber->house_subscriber_id, $subscribers))
                                    ->in('service', [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]),
                                setting()->columns(['subscriber_id'])
                            );

                            $blocks = array_reduce($blocks, function (array $carry, SubscriberBlock $item): array {
                                $carry[$item->subscriber_id] = true;

                                return $carry;
                            }, []);

                            $subscribers = array_filter($subscribers, static fn(HouseSubscriber $subscriber) => !array_key_exists($subscriber->house_subscriber_id, $blocks));

                            echo json_encode([
                                'success' => true,
                                'data' => [
                                    'auto_open' => false,
                                    'dtmf' => $device->resolver->string(ConfigKey::SipDtmf, '1'),

                                    'call_cms' => !$cmsConnected && $hasCms,
                                    'call_sip' => $flat->sip_enabled == 1,

                                    'caller' => $entrance->caller_id . ', ' . $flat->flat,

                                    'flat_id' => $flat->house_flat_id,
                                    'flat_number' => $flat->flat,

                                    'subscribers' => array_values(array_map(static fn(HouseSubscriber $subscriber) => $subscriber->house_subscriber_id, $subscribers))
                                ]
                            ]);
                        } catch (Throwable $throwable) {
                            echo json_encode(['success' => false, 'message' => $throwable->getMessage() . ' | ' . json_encode($params)]);
                        }

                        break;

                    case 'send':
                        try {
                            $device = intercom($params['domophone_id']);

                            if (!$device) {
                                echo json_encode(['success' => false, 'message' => 'Не удалось найти домофон | ' . json_encode($params)]);

                                break;
                            }

                            $server = $device->intercom->sipServer;

                            if (!$server) {
                                echo json_encode(['success' => false, 'message' => 'Отсуствует сип сервер у домофона | ' . json_encode($params)]);

                                break;
                            }

                            $flat = HouseFlat::findById($params['flat_id'], setting: setting()->columns(['address_house_id'])->nonNullable());
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

                            $stun = container(SipFeature::class)->stun();

                            $subscribers = HouseSubscriber::fetchAll(criteria()->in('house_subscriber_id', $params['subscribers']));

                            foreach ($subscribers as $subscriber) {
                                $token = $subscriber->push_token;
                                $voip = false;

                                if ($subscriber->voip_enabled && $subscriber->voip_token && $subscriber->voip_token != "off") {
                                    $token = $subscriber->voip_token;
                                    $voip = true;
                                }

                                $push = [
                                    'token' => $token,
                                    'type' => $subscriber->push_token_type,
                                    'hash' => $params['hash'],
                                    'extension' => $params['extensions'][strval($subscriber->house_subscriber_id)],
                                    'server' => $server->external_ip,
                                    'port' => $server->external_port,
                                    'transport' => 'udp',
                                    'dtmf' => $device->resolver->string(ConfigKey::SipDtmf, '1'),
                                    'timestamp' => time(),
                                    'ttl' => 30,
                                    'platform' => $subscriber->platform !== 0 ? 'ios' : 'android',
                                    'callerId' => $params['caller_id'] ?: 'WebRTC',
                                    'domophoneId' => $params['domophone_id'],
                                    'flatId' => $params['flat_id'],
                                    'flatNumber' => intval($params['flat_number']),
                                    'voipEnabled' => $voip ? 1 : 0,
                                    'title' => $address,
                                ];

                                if ($stun) {
                                    $push['stun'] = $stun;
                                    $push['stunTransport'] = 'udp';
                                }

                                container(ExternalFeature::class)->push($push);
                            }

                            echo json_encode(['success' => true]);
                        } catch (Throwable $throwable) {
                            echo json_encode(['success' => false, 'message' => $throwable->getMessage() . ' | ' . json_encode($params)]);
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

                    case 'domophone':
                        $device = intercom(intval($params));

                        if (!$device) {
                            break;
                        }

                        echo json_encode([
                            'dtmf' => $device->resolver->string(ConfigKey::SipDtmf, '1'),
                            'sosNumber' => $device->resolver->string(ConfigKey::SipSos),
                            'ip' => $device->intercom->ip
                        ]);

                        break;

                    case 'sos':
                        $device = intercom(intval($params));

                        if (!$device) {
                            echo json_encode(['sos_number' => env('SOS_NUMBER', '112')]);

                            break;
                        }

                        echo json_encode(['sos_number' => $device->resolver->string(ConfigKey::SipSos, '112')]);

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

        $server = parse_url((string)config_get('api.asterisk'));

        if ($server && $server['path']) {
            $path = substr((string)$path, strlen($server['path']));
        }

        if ($path && $path[0] == '/') {
            $path = substr((string)$path, 1);
        }

        return explode('/', (string)$path);
    }

    private function getExtension(string $extension, string $section): array
    {
        if ($extension[0] === '1' && strlen($extension) === 6) {
            $intercom = DeviceIntercom::findById((int)substr($extension, 1), setting: setting()->columns(['credentials']));

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
            $call = json_decode(container(RedisService::class)->get('call/user/' . $extension), true);

            if ($call) {
                return match ($section) {
                    'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes'],
                    'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $call['hash']],
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
            $flat = HouseFlat::findById((int)substr($extension, 1), setting: setting()->columns(['house_flat_id', 'sip_password']));

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
                $sipUserId = (int)substr($extension, 1);
                $sipUser = SipUser::findById($sipUserId, criteria()->equal('type', (int)$extension[0]), setting()->columns(['title', 'password']));

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
            $result .= urldecode($key) . '=' . urldecode((string)$value) . '&';
        }

        return $result;
    }
}
