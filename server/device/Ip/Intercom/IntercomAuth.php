<?php

namespace Selpol\Device\Ip\Intercom;

enum IntercomAuth
{
    case ANY_SAFE;
    case BASIC;
    case DIGEST;
}