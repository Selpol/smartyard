<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\AsteriskRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

exit((new Kernel(new AsteriskRunner()))->bootstrap()->run([]));