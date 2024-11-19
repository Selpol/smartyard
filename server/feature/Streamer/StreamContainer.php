<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

enum StreamContainer: string
{
    case CAMERA = 'camera';
    case SERVER = 'server';
}