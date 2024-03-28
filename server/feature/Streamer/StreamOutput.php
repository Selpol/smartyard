<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

enum StreamOutput
{
    case HLS;
    case RTC;
}