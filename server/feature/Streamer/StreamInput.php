<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

enum StreamInput
{
    case RTSP;
    case HLS;
}