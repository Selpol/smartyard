<?php

namespace backends\geocoder;

use backends\backend;

abstract class geocoder extends backend
{
    public abstract function suggestions(string $search): bool|array;
}
