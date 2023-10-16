<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\AsteriskRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new AsteriskRunner());

$kernel->getRunner()->setLogger(file_logger('asterisk'));

exit($kernel->bootstrap()->run([]));