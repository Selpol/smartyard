<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Flat;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Task\Task;

class FlatCodeDeleteTask extends Task
{
    public int $flatId;

    public function __construct(int $flatId)
    {
        parent::__construct('Синхронизация кода (' . $flatId . ')');

        $this->flatId = $flatId;

        $this->setLogger(file_logger('flat'));
    }

    public function onTask(): bool
    {
        $result = true;

        $flat = HouseFlat::findById($this->flatId, setting: setting()->columns(['flat', 'open_code'])->nonNullable());

        foreach ($flat->entrances as $entrance) {
            $intercom = intercom($entrance->house_domophone_id);

            if ($intercom instanceof CodeInterface) {
                if (!$intercom->removeCode(new Code(0, intval($flat->flat)))) {
                    $result = false;
                }
            }
        }

        if ($result) {
            $flat->open_code = '';
            $flat->open_code_enabled = null;

            $result = $flat->safeUpdate();

            $this->logger?->debug('Удален код с квартиры ', ['id' => $flat->house_flat_id, 'flat' => $flat->flat]);
        } else {
            $this->logger?->error('Не удалось удалить код с квартиры', ['id' => $flat->house_flat_id, 'flat' => $flat->flat]);
        }

        return $result;
    }
}