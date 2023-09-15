<?php

namespace backends\oauth;

use backends\backend;

abstract class oauth extends backend
{
    public abstract function register(string $mobile): ?string;
}
