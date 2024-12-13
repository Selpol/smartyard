<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

enum DvrStream: string
{
    case CAMERA = "camera";
    case ONLINE = "online";
    case ARCHIVE = "archive";
}