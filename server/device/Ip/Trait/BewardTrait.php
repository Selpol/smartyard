<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

use Selpol\Device\Exception\DeviceException;
use Throwable;

trait BewardTrait
{
    public function getSysInfo(): array
    {
        try {
            $response = $this->get('/cgi-bin/systeminfo_cgi', ['action' => 'get'], parse: false);

            logger('intercom')->debug('BewardTrait getSysInfo()', ['response' => $response]);

            return $this->parseParamValueHelp($response);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    protected function parseParamValueHelp(string $response): array
    {
        $return = [];

        $result = explode(PHP_EOL, $response);

        foreach ($result as $item) {
            $value = explode('=', trim($item));

            $return[trim($value[0])] = array_key_exists(1, $value) ? trim($value[1]) : true;
        }

        return $return;
    }
}