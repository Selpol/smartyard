<?php

declare(strict_types=1);

namespace Selpol\Feature\Intercom\Internal;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Feature\Intercom\IntercomApproved;
use Selpol\Feature\Intercom\IntercomFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Entity\Database\EntityConnectionInterface;
use Selpol\Framework\Http\Uri;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Service\DeviceService;
use SensitiveParameter;
use Throwable;

readonly class InternalIntercomFeature extends IntercomFeature
{
    public function updatePassword(DeviceIntercom $interom, #[SensitiveParameter] ?string $password): void
    {
        $device = intercom($interom->house_domophone_id);

        if (!$device) {
            throw new RuntimeException('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            throw new DeviceException($device, 'Домофон не доступен', code: 400);
        }

        $deviceCamera = DeviceCamera::fetch(criteria()->equal('url', $device->intercom->url));

        $password = $password ? $password : $device->resolver->string(ConfigKey::AuthPassword);

        if ($password) {
            if (str_starts_with($password, 'ENV_')) {
                $password = env(substr($password, 4), generate_password());
            }
        } else {
            $password = generate_password();
        }

        file_logger('password')->debug('Обновление пароля устройства', ['id' => $device->intercom->house_domophone_id, 'oldPassword' => $device->intercom->credentials, 'password' => $password]);

        try {
            $sipServer = container(SipFeature::class)->sip($device->intercom);

            $username = sprintf('1%05d', $device->intercom->house_domophone_id);

            if ($device instanceof SipInterface) {
                $device->setSip(new Sip($username, $password, $sipServer->internal_ip, $sipServer->internal_port));
            }
        } catch (Throwable $throwable) {
            file_logger('password')->error($throwable);

            throw new DeviceException($device, 'Неудалось обновить sip аккаунт домофона', code: 400);
        }

        $device->setLoginPassword($password);

        $oldIntercomPassword = $device->intercom->credentials;
        $device->intercom->credentials = $password;

        if (!$device->intercom->safeUpdate()) {
            file_logger('password')->info('Неудалось сохранить новый пароль в базе данных у домофона', ['old' => $oldIntercomPassword, 'new' => $password]);

            throw new DeviceException($device, 'Неудалось сохранить новый пароль в базе данных у домофона (новый пароль' . $password . ', старый пароль ' . $oldIntercomPassword . ')', code: 400);
        }

        if ($deviceCamera instanceof DeviceCamera) {
            $oldCameraPassword = $device->intercom->credentials;

            if ($deviceCamera->stream && trim($deviceCamera->stream) !== '') {
                $deviceCamera->stream = (string) (new Uri($deviceCamera->stream))->withUserInfo($device->login, $password);
            }

            $deviceCamera->credentials = $password;

            if (!$deviceCamera->safeUpdate()) {
                file_logger('password')->info('Неудалось сохранить новый пароль в базе данных у камеры', ['old' => $oldCameraPassword, 'new' => $password]);

                throw new DeviceException($device, 'Неудалось сохранить новый пароль в базе данных у камеры (новый пароль' . $password . ', старый пароль ' . $oldCameraPassword . ')', code: 400);
            }

            if ($deviceCamera->dvr_server_id) {
                try {
                    dvr($deviceCamera->dvr_server_id)->updateCamera($deviceCamera);
                } catch (Throwable $throwable) {
                    file_logger('password')->info('Неудалось обновить пароль на DVR' . PHP_EOL . $throwable, ['old' => $oldCameraPassword, 'new' => $password]);
                }
            }
        }

        if (container(AuditFeature::class)->canAudit()) {
            container(AuditFeature::class)->audit(strval($device->intercom->house_domophone_id), DeviceIntercom::class, 'password', 'Обновление пароля');
        }
    }

    public function getApproveds(): array
    {
        $usable = $this->getRedis()->approved();

        $result = [];

        $keys = $usable->keys('*');

        foreach ($keys as $key) {
            $value = $usable->get($key);

            if (!$value) {
                $result[$key] = null;

                continue;
            }

            $ttl = $usable->ttl($key);

            if (!$ttl) {
                $ttl = -1;
            }

            $approved = unserialize($value);

            if ($approved instanceof IntercomApproved) {
                $approved->ttl = $ttl;

                $result[$key] = $approved;
            }
        }

        return $result;
    }

    public function approved(string $id, string $title, string $name, ?string $model, #[SensitiveParameter] ?string $password, string $server, ?int $dvrServerId, ?int $frsServerId, ?int $addressHouseId, float $lat, float $lon): void
    {
        $value = $this->getRedis()->approved()->take($id);

        if ($value == null) {
            return;
        }

        $approved = unserialize($value);

        if ($approved instanceof IntercomApproved) {
            $database = container(EntityConnectionInterface::class);

            try {
                $database->beginTransaction();

                $intercom = $approved->intercom($server, $title, $model, $password);

                if ($dvrServerId) {
                    $dvr = dvr($dvrServerId);

                    if (!$dvr) {
                        throw new KernelException('Не удалось найти Dvr сервер', code: 404);
                    }

                    $device = container(DeviceService::class)->intercomByEntity($intercom);

                    $camera = $approved->camera($device, $dvr, $title, $name, $frsServerId, $lat, $lon);

                    if ($addressHouseId) {
                        $house = AddressHouse::findById($addressHouseId);

                        if (!$house) {
                            throw new KernelException('Не удалось найти дом', code: 404);
                        }

                        $entrance = $approved->entrance($intercom, $camera, $name, $lat, $lon);

                        $house->entrances()->add($entrance);
                    }
                }

                $database->commit();
            } catch (Throwable $throwable) {
                $database->rollBack();

                throw $throwable;
            }
        }
    }

    public function addApproved(IntercomApproved $approved): void
    {
        $this->getRedis()->approved()->setEx($approved->ip, $approved->ttl, serialize($approved));
    }

    public function removeApproved(string $id): void
    {
        $this->getRedis()->approved()->del($id);
    }
}
