<?php declare(strict_types=1);

namespace Selpol\Service\Asterisk;

use Psr\Http\Message\UriInterface;

readonly class Contact
{
    public string $value;
    public UriInterface $uri;
    public string $ip;

    public function __construct(string $value, UriInterface $uri, string $ip)
    {
        $this->value = $value;
        $this->uri = $uri;
        $this->ip = $ip;
    }
}
