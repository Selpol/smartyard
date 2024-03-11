<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

enum DvrStream: string
{
    case ONLINE = "online";
    case ARCHIVE = "archive";
}