<?php

namespace Selpol\Controller\Mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Task\Tasks\RecordTask;
use Selpol\Validator\Rule;

class ArchiveController extends Controller
{
    public function prepare(): Response
    {
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), [
            'id' => [Rule::id()],
            'from' => [Rule::required()],
            'to' => [Rule::required()]
        ]);

        $cameraId = $validate['id'];

        // приложение везде при работе с архивом передаёт время по часовому поясу Москвы.
        date_default_timezone_set('Europe/Moscow');

        $from = strtotime($validate['from']);
        $to = strtotime($validate['to']);

        if (!$from || !$to)
            return $this->rbtResponse(400, message: 'Неверный формат данных');

        $dvr_exports = backend("dvr_exports");

        // проверяем, не был ли уже запрошен данный кусок из архива.
        $check = $dvr_exports->checkDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

        if (@$check['id'])
            return $this->rbtResponse(200, $check['id']);

        $result = (int)$dvr_exports->addDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

        dispatch_low(new RecordTask($user["subscriberId"], $result));

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