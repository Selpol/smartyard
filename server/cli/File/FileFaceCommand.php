<?php declare(strict_types=1);

namespace Selpol\Cli\File;

use Exception;
use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\GridFS\Exception\FileNotFoundException;
use Selpol\Entity\Model\Frs\FrsFace;
use Selpol\Feature\File\File;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileMetadata;
use Selpol\Feature\File\FileStorage;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Throwable;

#[Executable('file:face', 'Миграция файлов лиц')]
class FileFaceCommand
{
    /**
     * @throws Exception
     */
    #[Execute]
    public function execute(CliIO $io, FileFeature $feature): void
    {
        try {
            $client = new Client(env('OLD_MONGO_URI'));

            /**
             * @var \MongoDB\Database
             */
            $database = $client->{env('OLD_MONGO_DATABASE', FileFeature::DEFAULT_DATABASE)};
            $bucket = $database->selectGridFSBucket();
    
            $faces = FrsFace::fetchAll();
    
            $bar = $io->getOutput()->getBar('Мигрировано ' . count($faces));
    
            $count = 0;
            $step = 100.0 / count($faces);
            $percent = 0.0;
    
            $bar->show();
    
            foreach ($faces as $face) {
                $count++;
                $io->writeLine('Proccess: ' . $count . PHP_EOL);

                try {
                    $fileId = new ObjectId($feature->fromGUIDv4($face->face_uuid));
                    $stream = $bucket->openDownloadStream($fileId);
                } catch (Throwable $throwable) {
                    if ($throwable instanceof FileNotFoundException) {
                        file_logger('face')->debug('empty', $face->jsonSerialize());
    
                        continue;
                    }
    
                    $io->writeLine($throwable);
    
                    continue;
                }
    
                $face_uuid = $feature->toGUIDv4(
                    $feature->addFile(
                        File::stream(stream($stream))
                            ->withFilename('face')
                            ->withMetadata(FileMetadata::contentType('image/jpeg')->withFaceId($face->face_id)),
                        FileStorage::Face
                    )
                );
    
                $face->face_uuid = trim($face_uuid);
                $face->update();
    
                $percent += $step;
                $bar->set((int) ceil($percent));
            }
    
            $bar->hide();
        } catch(Throwable $throwable) {
            $io->writeLine($throwable->__tostring()); 
        }
    }
}