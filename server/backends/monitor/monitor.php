<?php

namespace backends\monitor;

use backends\backend;

abstract class monitor extends backend
{
    public abstract function ping(int $id): bool;

    public abstract function sip(int $id): bool;
}