<?php

namespace Selpol\Task\Tasks\Plog;

use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Throwable;

class PlogOpenTask extends PlogTask
{
    /** @var int Тип события */
    public int $type;

    /** @var int Выход устройства */
    public int $door;

    /** @var int Дата события */
    public int $date;

    /** @var string Информация о событие */
    public string $detail;

    public int $retry = 3;

    public function __construct(int $id, int $type, int $door, int $date, string $detail)
    {
        parent::__construct($id, 'Событие открытие двери');

        $this->type = $type;
        $this->door = $door;
        $this->date = $date;
        $this->detail = $detail;
    }

    public function onTask(): bool
    {
        $logger = file_logger('plog');

        $logger->debug('Plog open task', ['type' => $this->type, 'id' => $this->id]);

        $plog = container(PlogFeature::class);

        $event_data = [];
        $event_id = false;
        $flat_list = [];

        $event_data[PlogFeature::COLUMN_DATE] = $this->date;
        $event_data[PlogFeature::COLUMN_EVENT] = $this->type;
        $event_data[PlogFeature::COLUMN_DOMOPHONE]['domophone_id'] = $this->id;
        $event_data[PlogFeature::COLUMN_DOMOPHONE]['domophone_output'] = $this->door;
        $event_data[PlogFeature::COLUMN_DOMOPHONE]['domophone_description'] = $this->getDomophoneDescription($event_data[PlogFeature::COLUMN_DOMOPHONE]['domophone_output']);
        $event_data[PlogFeature::COLUMN_EVENT_UUID] = guid_v4();

        if ($this->type == PlogFeature::EVENT_OPENED_BY_KEY) {
            $event_data[PlogFeature::COLUMN_OPENED] = 1;
            $rfid_key = $this->detail;
            $event_data[PlogFeature::COLUMN_RFID] = $rfid_key;

            $flat_list = $this->getFlatIdByRfid($rfid_key);

            $logger->debug('Plog open task by key', ['id' => $this->id, 'detail' => $this->detail]);

            if (count($flat_list) == 0)
                return false;
        }

        if ($this->type == PlogFeature::EVENT_OPENED_BY_CODE) {
            $event_data[PlogFeature::COLUMN_OPENED] = 1;
            $open_code = $this->detail;
            $event_data[PlogFeature::COLUMN_CODE] = $open_code;
            $flat_list = $this->getFlatIdByCode($open_code);

            if (count($flat_list) == 0)
                return false;
        }

        if ($this->type == PlogFeature::EVENT_OPENED_BY_APP) {
            $event_data[PlogFeature::COLUMN_OPENED] = 1;
            $user_phone = $this->detail;
            $event_data[PlogFeature::COLUMN_PHONES]['user_phone'] = $user_phone;
            $flat_list = $this->getFlatIdByUserPhone($user_phone);

            if (!$flat_list || count($flat_list) == 0)
                return false;
        }

        if ($this->type == PlogFeature::EVENT_OPENED_BY_FACE) {
            $event_data[PlogFeature::COLUMN_OPENED] = 1;

            $details = explode("|", $this->detail);

            $face_id = $details[0];
            $event_id = $details[1];

            $households = container(HouseFeature::class);

            $entrance = $households->getEntrances("domophoneId", ["domophoneId" => $this->id, "output" => $this->door])[0];

            $flat_list = container(FrsFeature::class)->getFlatsByFaceId($face_id, $entrance["entranceId"]);

            if (!$flat_list || count($flat_list) == 0)
                return false;
        }

        $image_data = $plog->getCamshot($this->id, $this->date, $event_id);

        if ($image_data) {
            if (isset($image_data[PlogFeature::COLUMN_IMAGE_UUID]))
                $event_data[PlogFeature::COLUMN_IMAGE_UUID] = $image_data[PlogFeature::COLUMN_IMAGE_UUID];

            $event_data[PlogFeature::COLUMN_PREVIEW] = $image_data[PlogFeature::COLUMN_PREVIEW];

            if (isset($image_data[PlogFeature::COLUMN_FACE])) {
                $event_data[PlogFeature::COLUMN_FACE] = $image_data[PlogFeature::COLUMN_FACE];

                if (isset($face_id))
                    $event_data[PlogFeature::COLUMN_FACE][FrsFeature::P_FACE_ID] = $face_id;
            }
        }

        $plog->writeEventData($event_data, $flat_list);

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        file_logger('task')->debug('PlogOpenTask error' . PHP_EOL . $throwable);

        $this->retryLow(300);
    }
}