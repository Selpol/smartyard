<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Task\Tasks\RecordTask;

class ArchiveController extends Controller
{
    public function prepare(): Response
    {
        $user = $this->getSubscriber();

        $body = $this->request->getParsedBody();

        $cameraId = (int)@$body['id'];

        // приложение везде при работе с архивом передаёт время по часовому поясу Москвы.
        date_default_timezone_set('Europe/Moscow');
        $from = strtotime(@$body['from']);
        $to = strtotime(@$body['to']);

        if (!$cameraId || !$from || !$to)
            return $this->rbtResponse(400, message: 'Неверный формат данных');

        $dvr_exports = backend("dvr_exports");

        // проверяем, не был ли уже запрошен данный кусок из архива.
        $check = $dvr_exports->checkDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

        if (@$check['id'])
            return $this->rbtResponse(200, $check['id']);

        $result = (int)$dvr_exports->addDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

        task(new RecordTask($user["subscriberId"], $result))->low()->dispatch();

        return $this->rbtResponse(200, $result);
    }

    public function download(): Response
    {
        $uuid = $this->getRoute()->getParam('uuid');

        if ($uuid === null)
            return $this->rbtResponse(404, message: 'Не указан идентификатор');

        $files = backend('files');

        $stream = $files->getFileStream($uuid);
        $info = $files->getFileInfo($uuid);

        return $this->response()
            ->withHeader('Content-Type', 'video/mp4')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $info['filename'])
            ->withStream($stream);
    }
}