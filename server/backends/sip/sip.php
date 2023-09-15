<?php

namespace backends\sip;

use backends\backend;

abstract class sip extends backend
{
    abstract public function server(string $by, string|int|null $query = null): array;

    abstract public function stun(string|int $extension): bool|string;
}
