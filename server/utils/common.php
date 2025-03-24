<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Selpol\Framework\Entity\EntitySetting;
use Selpol\Framework\Http\Request;
use Selpol\Framework\Http\Response;

$lastError = false;

if (!function_exists('check_string')) {
    function check_string(&$str, array $options = []): bool
    {
        $str = trim($str);

        if (array_key_exists("validChars", $options)) {
            $t = "";

            for ($i = 0; $i < mb_strlen($str); $i++) {
                if (in_array(mb_substr($str, $i, 1), $options["validChars"])) {
                    $t .= mb_substr($str, $i, 1);
                }
            }

            $str = $t;
        }

        if (!in_array("dontStrip", $options)) {
            $str = preg_replace('/\s+/', ' ', $str);
        }

        if (array_key_exists("minLength", $options) && mb_strlen($str) < $options["minLength"]) {
            return false;
        }

        if (array_key_exists("maxLength", $options) && mb_strlen($str) > $options["maxLength"]) {
            return false;
        }

        return true;
    }
}

if (!function_exists('last_error')) {
    function last_error(string|bool|null $error = null): string|bool|null
    {
        global $lastError;

        if (!is_null($error)) {
            $lastError = $error;
        }

        return $lastError;
    }
}

if (!function_exists('guid_v4')) {
    function guid_v4(): string
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);

            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        mt_srand((float)microtime() * 10000);

        $char = strtolower(md5(uniqid(rand(), true)));

        $hyphen = chr(45);

        return substr($char, 0, 8) . $hyphen .
            substr($char, 8, 4) . $hyphen .
            substr($char, 12, 4) . $hyphen .
            substr($char, 16, 4) . $hyphen .
            substr($char, 20, 12);
    }
}

if (!function_exists('connection_ip')) {
    function connection_ip(ServerRequestInterface $request): ?string
    {
        $ip = $request->getHeader('X-Real-Ip');

        if (count($ip) > 0 && filter_var($ip[0], FILTER_VALIDATE_IP)) {
            return $ip[0];
        }

        $ip = $request->getHeader('X-Forwarded-For');

        if (count($ip) > 0 && filter_var($ip[0], FILTER_VALIDATE_IP)) {
            return $ip[0];
        }

        $ip = @$request->getServerParams()['REMOTE_ADDR'];

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return null;
    }
}

if (!function_exists('ip_in_range')) {
    function ip_in_range(string $ip, string $range): bool
    {
        if (!strpos($range, '/')) {
            $range .= '/32';
        }

        list($range, $netmask) = explode('/', $range, 2);

        $ip_decimal = ip2long($ip);
        $range_decimal = ip2long($range);
        $netmask_decimal = ~(pow(2, (32 - $netmask)) - 1);

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}

if (!function_exists('temp_stream')) {
    /**
     * @param string $content
     * @return bool|resource
     */
    function temp_stream(string $content)
    {
        $stream = fopen("php://temp", "w+");

        fwrite($stream, $content, strlen($content));
        fseek($stream, 0);

        return $stream;
    }
}

if (!function_exists('generate_password')) {
    function generate_password(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
}

if (!function_exists('setting')) {
    function setting(): EntitySetting
    {
        return new EntitySetting();
    }
}

if (!function_exists('rbt_response')) {
    function rbt_response(int $code = 200, ?string $message = null): ResponseInterface
    {
        $name = array_key_exists($code, Response::$codes) ? Response::$codes[$code]['name'] : ($message ?: 'Unknown error');

        return json_response($code, body: [
            'code' => $code,
            'name' => $name,
            'message' => $message ?: $name
        ]);
    }
}

if (!function_exists('user_response')) {
    function user_response(int $code = 200, mixed $data = null, ?string $name = null, ?string $message = null): ResponseInterface
    {
        if ($code !== 204) {
            $body = ['code' => $code];

            if ($message === null) {
                if ($name) {
                    $message = $name;
                } else if (array_key_exists($code, Response::$codes)) {
                    $message = Response::$codes[$code]['name'];
                }
            }

            if ($name === null) {
                if (array_key_exists($code, Response::$codes)) {
                    $body['name'] = Response::$codes[$code]['name'];
                }
            } else {
                $body['name'] = $name;
            }

            if ($message !== null) {
                $body['message'] = $message;
            }

            if ($data !== null) {
                $body['data'] = $data;
            }

            return json_response($code, body: $body);
        }

        return response($code);
    }
}

if (!function_exists('mobile_mask')) {
    function mobile_mask(?string $value): string
    {
        if (is_null($value) || $value === '' || strlen($value) !== 11) {
            return '7**********';
        }

        return $value[0] . '******' . substr($value, 7);
    }
}

if (!function_exists('client_request')) {
    function client_request(string $method, string|UriInterface $uri): RequestInterface
    {
        return new Request($method, $uri, []);
    }
}
