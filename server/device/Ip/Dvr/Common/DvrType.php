<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

enum DvrType: string
{
    case FLUSSONIC = "flussonic";
    case TRASSIR = "trassir";
}