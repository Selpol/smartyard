<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\RouterRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new RouterRunner());

$kernel->getRunner()->setLogger(file_logger('index'));

exit($kernel->bootstrap()->run(['router' => 'internal']));