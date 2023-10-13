<?php declare(strict_types=1);

namespace Selpol\Runner;

use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Http\Uri;
use Throwable;

class MqttRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    public function run(array $arguments): int
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return $this->bad();

        $mqtt = config_get('mqtt');

        $ip = $_SERVER['REMOTE_ADDR'];

        $trust = false;

        foreach ($mqtt['trust'] as $range)
            if (ip_in_range($ip, $range)) {
                $trust = true;

                break;
            }

        if (!$trust)
            return $this->bad();

        $uri = new Uri($_SERVER['REQUEST_URI']);
        $path = $uri->getPath();

        if ($path === '/mqtt/user') {
            if (!array_key_exists('username', $_POST) || !array_key_exists('password', $_POST) || !array_key_exists('clientid', $_POST))
                return $this->bad();

            if (container(MqttFeature::class)->checkUser($_POST['username'], $_POST['password'], $_POST['clientid']))
                return $this->ok();
        } else if ($path === '/mqtt/admin') {
            if (!array_key_exists('username', $_POST))
                return $this->bad();

            if (container(MqttFeature::class)->checkAdmin($_POST['username']))
                return $this->ok();
        } else if ($path === '/mqtt/acl') {
            if (!array_key_exists('username', $_POST) || !array_key_exists('clientid', $_POST) || !array_key_exists('topic', $_POST) || !array_key_exists('acc', $_POST))
                return $this->bad();

            if (container(MqttFeature::class)->checkAcl($_POST['username'], $_POST['clientid'], $_POST['topic'], intval($_POST['acc'])))
                return $this->ok();
        }

        return $this->bad();
    }

    public function error(Throwable $throwable): int
    {
        $this->bad();

        return 0;
    }

    private function ok(): int
    {
        header('HTTP/1.0 400 Bad Request');
        header('Content-Type: application/json');

        echo '{ "Ok": true, "Error": "OK" }';

        return 0;
    }

    private function bad(): int
    {
        header('HTTP/1.0 400 Bad Request');
        header('Content-Type: application/json');

        echo '{ "Ok": false, "Error": "Bad Request" }';

        return 0;
    }
}