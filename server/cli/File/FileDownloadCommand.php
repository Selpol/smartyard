<?php declare(strict_types=1);

namespace Selpol\Cli\File;

use Exception;
use Selpol\Feature\File\FileFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('file:download', 'Скачать файл по его названию')]
class FileDownloadCommand
{
    /**
     * @throws Exception
     */
    #[Execute]
    public function execute(CliIO $io, string $path, FileFeature $feature): void
    {
        $filename = $io->readLine('Имя файла');

        $uuids = $feature->searchFiles(['filename' => $filename]);

        if (count($uuids) > 0) {
            $stream = stream($feature->getFileStream($uuids[0]));

            file_put_contents($path, $stream->getContents());
        }
    }
}