<?php

namespace Selpol\Task\Tasks;

use chillerlan\QRCode\QRCode;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Selpol\Feature\Address\AddressFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;
use ZipArchive;

class QrTask extends Task implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public int $houseId;
    public bool $override;

    public function __construct(int $houseId, bool $override)
    {
        parent::__construct('Qr (' . $houseId . ')');

        $this->houseId = $houseId;
        $this->override = $override;
    }

    public function onTask(): ?string
    {
        $file = container(FileFeature::class);

        $house = container(AddressFeature::class)->getHouse($this->houseId);

        $uuids = $file->searchFiles(['filename' => $house['houseFull'] . ' QR.zip']);

        if ($this->override) {
            foreach ($uuids as $uuid)
                $file->deleteFile($uuid['id']);
        } else {
            if (count($uuids) > 0)
                return $uuids[count($uuids) - 1]['id'];
        }

        $qr = $this->getOrCreateQr($house);

        $this->setProgress(25);

        return $this->createQrZip($qr);
    }

    private function getOrCreateQr(array $house): array
    {
        $households = container(HouseFeature::class);

        $flats = $households->getFlats('houseId', $this->houseId);

        $result = ['address' => $house['houseFull'], 'flats' => []];

        foreach ($flats as $flat) {
            if (!isset($flat['code']) || $flat['code'] == '') {
                $code = $this->getCode($flat['flatId']);

                $flat['code'] = $code;

                $households->modifyFlat($flat['flatId'], ['code' => $code]);
            }

            $result['flats'][] = ['flat' => $flat['flat'], 'code' => $flat['code']];
        }

        return $result;
    }

    private function createQrZip(array $qr): ?string
    {
        $file = tempnam(Settings::getTempDir(), 'qr-zip');
        $files = [];

        try {
            $zip = new ZipArchive();
            $zip->open($file, ZipArchive::OVERWRITE);

            foreach ($qr['flats'] as $flat) {
                $codeFile = tempnam(Settings::getTempDir(), 'qr');
                $files[] = $codeFile;

                (new QRCode())->render($flat['code'], $codeFile);

                $template = new TemplateProcessor(path('private/qr-template.docx'));

                $template->setValue('address', $qr['address'] . ', кв ' . $flat['flat']);
                $template->setImageValue('qr', ['path' => $codeFile, 'width' => 96, 'height' => 96]);

                $templateFile = $template->save();
                $files[] = $templateFile;

                $zip->addFile($templateFile, $flat['flat'] . '.docx');
            }

            $zip->close();

            return container(FileFeature::class)->addFile($qr['address'] . ' QR.zip', fopen($file, "r"));
        } catch (Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        } finally {
            unlink($file);

            foreach ($files as $file)
                unlink($file);
        }
    }

    private function getCode(int $flatId): string
    {
        return $this->houseId . '-' . $flatId . '-' . md5(guid_v4());
    }
}