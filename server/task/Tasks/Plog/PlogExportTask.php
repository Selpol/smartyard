<?php

declare(strict_types=1);

namespace Selpol\Task\Tasks\Plog;

use MongoDB\GridFS\Exception\FileNotFoundException;
use PhpOffice\PhpWord\PhpWord;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileStorage;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Task\Task;
use PhpOffice\PhpWord\Settings;

class PlogExportTask extends Task
{
    /**
     * Квартира для экспорта событий
     * @var int
     */
    public int $flatId;

    /**
     * Тип события для эскпорта
     * @var
     */
    public ?int $type;

    /**
     * Начало экспорта событий
     * @var 
     */
    public ?int $startDate;

    /**
     * Конец экспорта событий
     * @var 
     */
    public ?int $endDate;

    public function __construct(
        int $flatId,
        ?int $type,
        ?int $startDate,
        ?int $endDate
    ) {
        parent::__construct('Экспорт событий с квартиры (' . $flatId . ')');

        $this->flatId = $flatId;

        $this->type = $type;

        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->setLogger(file_logger('plog-export'));
    }

    public function onTask(): bool
    {
        /**
         * @var FileFeature
         */
        $file = container(FileFeature::class);

        $plogs = container(PlogFeature::class)->getAllEventsForFlat($this->flatId, $this->type, $this->startDate, $this->endDate);

        if (!$plogs) {
            $this->logger?->debug('Не удалось найти события для квартиры');

            return true;
        }

        if (count($plogs) == 0) {
            $this->logger?->debug('Событий для квартиры нету');

            return true;
        }

        $document = new PhpWord();

        $progress = 0;
        $step = 100 / count($plogs);

        $this->setProgress(0);

        $section = $document->addSection();

        $tmps = [];

        try {
            foreach ($plogs as $plog) {
                $date = $plog['date'];
                $event = $plog['event'];
                $image = $plog['image_uuid'];

                $tmp = tempnam(Settings::getTempDir(), $image);

                $section->addText(date('Y-m-d H:i:s', $date) . ' - ' . $this->translateEvent($event));

                try {
                    $image = $file->getFile($file->fromGUIDv4($image), FileStorage::Screenshot);

                    $tmps[] = $tmp;

                    fwrite(fopen($tmp, 'w+'), $image->stream->getContents());

                    $section->addImage($tmp, ['width' => 384]);
                } catch (FileNotFoundException) {
                    continue;
                }

                $progress += $step;

                $this->setProgress($progress);
            }

            $this->setProgress(100);

            $writer = new \PhpOffice\PhpWord\Writer\Word2007($document);
            $writer->save(path('private/' . $this->flatId . '.docx'));
        } finally {
            foreach ($tmps as $tmp) {
                unlink($tmp);
            }
        }

        return true;
    }

    private function translateEvent(int $event): string
    {
        switch ($event) {
            case 1:
                return "Пропущенный звонок";
            case 2:
                return "Звонок в домофон";
            case 3:
                return "Открытие ключом";
            case 4:
                return "Открытие из приложения";
            case 5:
                return "Открытие по лицу";
            case 6:
                return "Открытие по коду";
            case 7:
                return "Открытие ворот звонком";
            case 8:
                return "Открытие кнопкой";
            default:
                return "Неизвестное событие";
        }
    }
}
