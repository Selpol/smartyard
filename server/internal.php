<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\RouterRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

exit((new Kernel(new RouterRunner()))->bootstrap()->run(['router' => 'internal']));