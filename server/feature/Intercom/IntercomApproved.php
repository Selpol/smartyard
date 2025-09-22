<?php

declare(strict_types=1);

namespace Selpol\Feature\Intercom;

use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Framework\Kernel\Exception\KernelException;
use SensitiveParameter;

class IntercomApproved
{
    public string $ip;
    public string $mac;
    public string $host;
    public string $server;
    public string $model;

    public string $password;

    public int $ttl;

    public function __construct(string $ip, string $mac, string $host, string $server, string $model, string $password, int $ttl)
    {
        $this->ip = $ip;
        $this->mac = $mac;
        $this->host = $host;
        $this->server = $server;
        $this->model = $model;

        $this->password = $password;

        $this->ttl = $ttl;
    }

    public function last(): string
    {
        $segments = explode('.', $this->ip);

        return implode('.', array_slice($segments, 2));
    }

    public function intercom(string $server, string $title, ?string $model, #[SensitiveParameter] ?string $password): DeviceIntercom
    {
        $intercom = new DeviceIntercom();

        $intercom->model = $model ?: $this->model;
        $intercom->server = $server;
        $intercom->url = 'http://' . $this->ip;
        $intercom->credentials = $password ?: $this->password;

        $intercom->ip = $this->ip;

        $intercom->comment = $title;

        $intercom->hidden = false;

        $intercom->insert();

        return $intercom;
    }

    public function camera(IntercomDevice $device, DvrDevice $dvr, string $title, string $name, ?int $frsServerId, float $lat, float $lon): DeviceCamera
    {
        $template = $device->resolver->string(ConfigKey::AutoTemplateDvr, 'dom%ip%');
        $dvrId = $device::template($template, ['ip' => $this->last()]);

        if ($dvr->getCamera($dvrId) != null) {
            throw new KernelException('Камера ' . $dvrId . ' на Dvr сервере уже существует', code: 400);
        }

        $template = $device->resolver->string(ConfigKey::AutoTemplatePrimary);
        $primary = $template ? $device::template($template, ['username' => $device->login, 'password' => $device->password, 'ip', $this->ip]) : null;

        $template = $device->resolver->string(ConfigKey::AutoTemplateSecondary);
        $secondary = $template ? $device::template($template, ['username' => $device->login, 'password' => $device->password, 'ip' => $this->ip]) : null;

        $camera = new DeviceCamera();

        $camera->dvr_server_id = $dvr->server->id;
        $camera->frs_server_id = $frsServerId;

        $camera->enabled = 1;

        $camera->model = $device->resolver->string(ConfigKey::AutoCamera, 'fake');
        $camera->stream = $primary;
        $camera->url = 'http://' . $device->intercom->ip;
        $camera->credentials = $device->password;
        $camera->dvr_stream = $dvrId;
        $camera->name = $name;
        $camera->timezone = 'Europe/Moscow';

        $camera->lat = $lat;
        $camera->lon = $lon;

        $camera->comment = $title;

        $camera->hidden = false;

        $camera->insert();

        $dvr->addCamera($camera, $dvrId, $primary, $secondary);

        return $camera;
    }

    public function entrance(DeviceIntercom $intercom, DeviceCamera $camera, string $name, float $lat, float $lon): HouseEntrance
    {
        $entrance = new HouseEntrance();

        $entrance->caller_id = $name;

        $entrance->lat = $lat;
        $entrance->lon = $lon;

        $entrance->house_domophone_id = $intercom->house_domophone_id;
        $entrance->camera_id = $camera->camera_id;

        $entrance->domophone_output = 0;

        $entrance->insert();

        return $entrance;
    }
}
