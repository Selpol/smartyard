<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

enum StreamInput: string
{
    case RTSP = 'rtsp';
    case HLS = 'hls';
}