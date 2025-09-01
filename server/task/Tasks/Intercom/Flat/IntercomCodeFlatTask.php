<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Flat;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Task\Task;

class IntercomCodeFlatTask extends Task
{
    public int $flatId;

    public bool $block;

    public function __construct(int $flatId, bool $block)
    {
        parent::__construct('Синхронизация кода (' . $flatId . ', ' . $block . ')');

        $this->flatId = $flatId;
        $this->block = $block;

        $this->setLogger(file_logger('task-intercom'));
    }

    public function onTask(): bool
    {
        $flat = HouseFlat::findById($this->flatId, setting: setting()->columns(['flat', 'open_code'])->nonNullable());

        foreach ($flat->entrances as $entrance) {
            $intercom = intercom($entrance->house_domophone_id);

            if ($intercom instanceof CodeInterface) {
                if ($flat->open_code) {
                    $code = new Code(intval($flat->open_code), intval($flat->flat));

                    if ($this->block) {
                        $intercom->removeCode($code);
                    } else {
                        $intercom->addCode($code);
                    }
                } else {
                    $intercom->removeCode(new Code(0, intval($flat->flat)));
                }
            }
        }

        return true;
    }
}