<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Task\Task;

abstract class IntercomTask extends Task
{
    protected function __construct(
        /** @var int Идентификатор устройства */
        public int $id,
        string     $title
    )
    {
        parent::__construct($title);

        $this->setLogger(file_logger('task-intercom'));
    }
}