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

        $input = json_decode(file_get_contents('php://input', true), true);

        $uri = new Uri($_SERVER['REQUEST_URI']);
        $path = $uri->getPath();

        if ($path === '/mqtt/user') {
            if (!array_key_exists('username', $input) || !array_key_exists('password', $input) || !array_key_exists('clientid', $input))
                return $this->bad();

            if (container(MqttFeature::class)->checkUser($input['username'], $input['password'], $input['clientid']))
                return $this->ok();
        } else if ($path === '/mqtt/admin') {
            if (!array_key_exists('username', $input))
                return $this->bad();

            if (container(MqttFeature::class)->checkAdmin($input['username']))
                return $this->ok();
        } else if ($path === '/mqtt/acl') {
            if (!array_key_exists('username', $input) || !array_key_exists('clientid', $input) || !array_key_exists('topic', $input) || !array_key_exists('acc', $input))
                return $this->bad();

            if (container(MqttFeature::class)->checkAcl($input['username'], $input['clientid'], $input['topic'], intval($input['acc'])))
                return $this->ok();
        }

        return $this->bad();
    }

    public function error(Throwable $throwable): int
    {
        file_logger('mqtt')->error($throwable);

        $this->bad();

        return 0;
    }

    private function ok(): int
    {
        header('HTTP/1.0 200 OK');
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