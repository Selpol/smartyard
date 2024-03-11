<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

enum DvrContainer: string
{
    case RTSP = "rtsp";
    case HLS = "hls";
}