<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Beward;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Http\Stream;
use Throwable;

class BewardCamera extends CameraDevice
{
    public string $login = 'admin';

    public function getSysInfo(): array
    {
        try {
            $response = $this->client()->get($this->uri . '/cgi-bin/systeminfo_cgi?action=get');
            $body = $response->getBody()->getContents();

            return $this->parseParamValue($body);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function getScreenshot(): Stream
    {
        try {
            return $this->client()->get($this->uri . '/cgi-bin/images_cgi', ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)])->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    private function parseParamValue(string $response): array
    {
        $return = [];

        $result = explode('\n', $response);

        foreach ($result as $item) {
            $value = explode('=', trim($item));

            $return[trim($value[0])] = array_key_exists(1, $value) ? trim($value[1]) : true;
        }

        return $return;
    }
}