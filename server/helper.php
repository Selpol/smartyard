<?php

use backends\backend;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selpol\Cache\RedisCache;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Kernel\Kernel;
use Selpol\Logger\FileLogger;
use Selpol\Service\BackendService;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorException;
use Selpol\Validator\ValidatorMessage;

$lastError = false;

if (!function_exists('path')) {
    function path(string $value): string
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . $value;
    }
}

if (!function_exists('logger')) {
    function logger(string $channel): LoggerInterface
    {
        return FileLogger::channel($channel);
    }
}

if (!function_exists('kernel')) {
    function kernel(): ?Kernel
    {
        return Kernel::instance();
    }
}

if (!function_exists('env')) {
    function env(?string $key = null, ?string $default = null): mixed
    {
        if ($key !== null)
            return kernel()->getEnvValue($key, $default);

        return kernel()->getEnv();
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key !== null)
            return kernel()->getConfigValue($key, $default);

        return kernel()->getConfig();
    }
}

if (!function_exists('container')) {
    /**
     * @template T
     * @psalm-param class-string<T> $key
     * @return T
     * @throws NotFoundExceptionInterface
     */
    function container(string $key): mixed
    {
        return kernel()->getContainerValue($key);
    }
}

if (!function_exists('backend')) {
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function backend(string $backend, bool $login = false): backend|false
    {
        return container(BackendService::class)->get($backend, $login);
    }
}

if (!function_exists('task')) {
    function task(Task $task): TaskContainer
    {
        return new TaskContainer($task);
    }
}

if (!function_exists('dispatch_high')) {
    function dispatch_high(Task $task, ?int $delay = null): bool
    {
        return task($task)->delay($delay)->high()->dispatch();
    }
}

if (!function_exists('dispatch_default')) {
    function dispatch_default(Task $task, ?int $delay = null): bool
    {
        return task($task)->delay($delay)->default()->dispatch();
    }
}

if (!function_exists('dispatch_low')) {
    function dispatch_low(Task $task, ?int $delay = null): bool
    {
        return task($task)->delay($delay)->low()->dispatch();
    }
}

if (!function_exists('camera')) {
    /**
     * @throws NotFoundExceptionInterface
     */
    function camera(string $model, string $url, string $password): CameraDevice|false
    {
        return container(DeviceService::class)->camera($model, $url, $password);
    }
}

if (!function_exists('intercom')) {
    /**
     * @throws NotFoundExceptionInterface
     */
    function intercom(string $model, string $url, string $password): IntercomDevice|false
    {
        return container(DeviceService::class)->intercom($model, $url, $password);
    }
}

if (!function_exists('validator')) {
    /**
     * @param array $value
     * @param array $items
     * @return array
     * @throws \Selpol\Http\HttpException
     */
    function validator(array $value, array $items): array
    {
        $validator = new Validator($value, $items);

        try {
            return $validator->validate();
        } catch (ValidatorException $e) {
            throw new \Selpol\Http\HttpException(message: $e->getValidatorMessage()->getMessage(), code: 400);
        }
    }
}

if (!function_exists('validate')) {
    function validate(array $value, array $items): array|ValidatorMessage
    {
        $validator = new Validator($value, $items);

        try {
            return $validator->validate();
        } catch (ValidatorException $e) {
            return $e->getValidatorMessage();
        }
    }
}

if (!function_exists('redis_cache')) {
    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    function redis_cache(string $key, callable $default, DateInterval|int|null $ttl = null): mixed
    {
        $cache = container(RedisCache::class);

        $value = $cache->get($key);

        if ($value !== null)
            return $value;

        $value = call_user_func($default);

        $cache->set($key, $value, $ttl);

        return $value;
    }
}

if (!function_exists('check_int')) {
    function check_int(&$int): bool
    {
        $int = trim($int);
        $_int = strval((int)$int);

        if ($int != $_int)
            return false;
        else {
            $int = (int)$_int;
            return true;
        }
    }
}

if (!function_exists('check_string')) {
    function check_string(&$str, array $options = []): bool
    {
        $str = trim($str);

        if (array_key_exists("validChars", $options)) {
            $t = "";

            for ($i = 0; $i < mb_strlen($str); $i++)
                if (in_array(mb_substr($str, $i, 1), $options["validChars"]))
                    $t .= mb_substr($str, $i, 1);

            $str = $t;
        }

        if (!in_array("dontStrip", $options))
            $str = preg_replace('/\s+/', ' ', $str);

        if (array_key_exists("minLength", $options) && mb_strlen($str) < $options["minLength"])
            return false;

        if (array_key_exists("maxLength", $options) && mb_strlen($str) > $options["maxLength"])
            return false;

        return true;
    }
}

if (!function_exists('last_error')) {
    function last_error(string|bool|null $error = null): string|bool|null
    {
        global $lastError;

        if (!is_null($error))
            $lastError = $error;

        return $lastError;
    }
}

if (!function_exists('guid_v4')) {
    function guid_v4(bool $trim = true): string
    {
        // copyright (c) by Dave Pearson (dave at pds-uk dot com)
        // https://www.php.net/manual/ru/function.com-create-guid.php#119168

        if (function_exists('com_create_guid') === true) {
            if ($trim === true) return trim(com_create_guid(), '{}');
            else return com_create_guid();
        } else if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        mt_srand((double)microtime() * 10000);

        $char = strtolower(md5(uniqid(rand(), true)));

        $hyphen = chr(45);                  // "-"

        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"

        return $lbrace .
            substr($char, 0, 8) . $hyphen .
            substr($char, 8, 4) . $hyphen .
            substr($char, 12, 4) . $hyphen .
            substr($char, 16, 4) . $hyphen .
            substr($char, 20, 12) .
            $rbrace;
    }
}

if (!function_exists('connection_ip')) {
    function connection_ip(ServerRequestInterface $request): ?string
    {
        $ip = $request->getHeader('X-Real-Ip');

        if (count($ip) > 0 && filter_var($ip[0], FILTER_VALIDATE_IP))
            return $ip[0];

        $ip = $request->getHeader('X-Forwarded-For');

        if (count($ip) > 0 && filter_var($ip[0], FILTER_VALIDATE_IP))
            return $ip[0];

        $ip = @$request->getServerParams()['REMOTE_ADDR'];

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP))
            return $ip;

        return null;
    }
}

if (!function_exists('ip_in_range')) {
    function ip_in_range(string $ip, string $range): bool
    {
        if (!strpos($range, '/'))
            $range .= '/32';

        list($range, $netmask) = explode('/', $range, 2);

        $ip_decimal = ip2long($ip);
        $range_decimal = ip2long($range);
        $netmask_decimal = ~(pow(2, (32 - $netmask)) - 1);

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}