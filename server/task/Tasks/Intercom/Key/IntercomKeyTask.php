<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Key;

use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Service\DatabaseService;
use Selpol\Task\Task;

abstract class IntercomKeyTask extends Task
{
    public function __construct(public int $flatId, string $title)
    {
        parent::__construct($title);

        $this->setLogger(file_logger('task-intercom'));
    }

    protected function getFlat(): ?HouseFlat
    {
        return HouseFlat::findById($this->flatId, setting: setting()->columns(['flat']));
    }

    /**
     * @return HouseEntrance[]
     */
    protected function getEntrances(): array
    {
        $ids = container(DatabaseService::class)->get('SELECT house_entrance_id FROM houses_entrances_flats WHERE house_flat_id = :id', ['id' => $this->flatId]);

        return HouseEntrance::fetchAll(criteria()->in('house_entrance_id', array_values(array_unique(array_map(static fn(array $id) => $id['house_entrance_id'], $ids)))), setting()->columns(['house_domophone_id']));
    }
}