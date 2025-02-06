<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Task\Task;

abstract class IntercomTask extends Task
{
    private IntercomDevice $device;

    protected function __construct(
        /** @var int Идентификатор устройства */
        public int $id,
        string $title
    ) {
        parent::__construct($title);

        $this->setLogger(file_logger('task-intercom'));
    }

    protected function getDevice(): IntercomDevice
    {
        if (!isset($this->device)) {
            $this->device = intercom($this->id);
        }

        return $this->device;
    }
}