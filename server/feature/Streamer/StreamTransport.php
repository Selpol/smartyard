<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

enum StreamTransport: string
{
    case UDP = 'udp';
    case TCP = 'tcp';
}