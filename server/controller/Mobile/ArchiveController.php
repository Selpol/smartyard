<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Http\Response;
use Selpol\Task\Tasks\RecordTask;
use Selpol\Validator\Rule;

class ArchiveController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function prepare(): Response
    {
        $userId = $this->getUser()->getIdentifier();

        $validate = validator($this->request->getParsedBody(), [
            'id' => [Rule::id()],
            'from' => [Rule::required()],
            'to' => [Rule::required()]
        ]);

        $cameraId = $validate['id'];

        date_default_timezone_set('Europe/Moscow');

        $from = strtotime($validate['from']);
        $to = strtotime($validate['to']);

        if (!$from || !$to)
            return $this->rbtResponse(400, message: 'Неверный формат данных');

        $archive = container(ArchiveFeature::class);

        // проверяем, не был ли уже запрошен данный кусок из архива.
        $check = $archive->checkDownloadRecord($cameraId, $userId, $from, $to);

        if (@$check['id'])
            return $this->rbtResponse(200, $check['id']);

        $result = (int)$archive->addDownloadRecord($cameraId, $userId, $from, $to);

        task(new RecordTask($userId["subscriberId"], $result))->low()->dispatch();

        return $this->rbtResponse(200, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function download(): Response
    {
        $uuid = $this->getRoute()->getParam('uuid');

        if ($uuid === null)
            return $this->rbtResponse(404, message: 'Не указан идентификатор');

        $file = container(FileFeature::class);

        $stream = $file->getFileStream($uuid);
        $info = $file->getFileInfo($uuid);

        return $this->response()
            ->withHeader('Content-Type', 'video/mp4')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $info['filename'])
            ->withStream($stream);
    }
}