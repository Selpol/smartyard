<?php

namespace Selpol\Task\Tasks;

use backends\files\files;
use chillerlan\QRCode\QRCode;
use Exception;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Psr\Container\ContainerExceptionInterface;
use Selpol\Task\Task;
use ZipArchive;

class QrTask extends Task
{
    public int $houseId;
    public ?int $flatId;
    public bool $override;

    private files $files;

    public function __construct(int $houseId, ?int $flatId, bool $override)
    {
        parent::__construct('Qr (' . $houseId . ', ' . ($flatId ?? -1) . ')');

        $this->houseId = $houseId;
        $this->flatId = $flatId;
        $this->override = $override;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function onTask(): ?string
    {
        $this->files = backend('files');

        $house = backend('addresses')->getHouse($this->houseId);

        if ($this->override) {
            $uuids = $this->files->searchFiles(['filename' => $house['houseFull'] . ' QR.zip']);

            foreach ($uuids as $uuid)
                $this->files->deleteFile($uuid['id']);
        } else {
            $uuids = $this->files->searchFiles(['filename' => $house['houseFull'] . ' QR.zip']);

            if (count($uuids) > 0)
                return $uuids[count($uuids) - 1]['id'];
        }

        $qr = $this->getOrCreateQr($house);

        $this->setProgress(25);

        return $this->createQrZip($qr);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function getOrCreateQr(array $house): array
    {
        $households = backend('households');

        $flats = $households->getFlats('houseId', $this->houseId); // code

        $result = ['address' => $house['houseFull'], 'flats' => []];

        foreach ($flats as $flat) {
            if (!isset($flat['code']) || is_null($flat['code']) || $flat['code'] == '') {
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
                $zip->setCompressionName($flat['flat'] . '.docx', ZipArchive::CM_STORE);
            }

            $zip->close();

            return $this->files->addFile($qr['address'] . ' QR.zip', fopen($file, "r"));
        } catch (Exception) {
            return null;
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