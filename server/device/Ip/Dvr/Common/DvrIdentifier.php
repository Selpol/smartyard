<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use Psr\Http\Message\ServerRequestInterface;
use Selpol\Framework\Kernel\Exception\KernelException;

readonly class DvrIdentifier
{
    public function __construct(public int $camera, public int $dvr, public int $start, public int $end, public ?int $subscriber)
    {
    }

    public function toToken(): string
    {
        $subscriber = (is_null($this->subscriber) ? 'null' : $this->subscriber);

        $ip = connection_ip(container(ServerRequestInterface::class));
        $token = config_get('features.dvr.token');

        $salt = bin2hex(openssl_random_pseudo_bytes(16));
        $hash = sha1($this->camera . $this->dvr . $this->start . $this->end . $subscriber . $ip . $token . $salt);

        return $hash . $salt . $this->start . $this->end . '-' . $this->camera . '-' . $this->dvr . '-' . $subscriber;
    }

    public static function fromToken(string $value): DvrIdentifier
    {
        if (strlen($value) < 98 || strlen($value) > 184) {
            throw new KernelException('Не верная длина идентификатора');
        }

        $segments = explode('-', $value);

        if (count($segments) != 4) {
            throw new KernelException('Не верный токен доступа');
        }

        if (strlen($segments[0]) != 92) {
            throw new KernelException('Не верная длина токена');
        }

        $hash = substr($segments[0], 0, 40);
        $salt = substr($segments[0], 40, 32);
        $start = intval(substr($segments[0], 72, 10));
        $end = intval(substr($segments[0], 82, 10));

        $camera = intval($segments[1]);
        $dvr = intval($segments[2]);
        $subscriber = $segments[3];

        $ip = connection_ip(container(ServerRequestInterface::class));
        $token = config_get('features.dvr.token');

        if ($hash != sha1($camera . $dvr . $start . $end . $subscriber . $ip . $token . $salt)) {
            throw new KernelException('Не верный токен');
        }

        $time = time();

        if ($time < $start || $time > $end) {
            throw new KernelException('Время действия токена истекло');
        }

        return new DvrIdentifier($camera, $dvr, $start, $end, $subscriber == 'null' ? null : intval($subscriber));
    }
}