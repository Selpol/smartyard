<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\ArchivePrepareRequest;
use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\JwtMiddleware;
use Selpol\Middleware\MobileMiddleware;
use Selpol\Task\Tasks\RecordTask;

#[Controller('/mobile/cctv')]
readonly class ArchiveController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/recPrepare')]
    public function prepare(ArchivePrepareRequest $request, ArchiveFeature $archiveFeature): Response
    {
        $userId = $this->getUser()->getIdentifier();

        date_default_timezone_set('Europe/Moscow');

        $from = strtotime($request->from);
        $to = strtotime($request->to);

        if (!$from || !$to)
            return user_response(400, message: 'Неверный формат данных');

        // проверяем, не был ли уже запрошен данный кусок из архива.
        $check = $archiveFeature->checkDownloadRecord($request->id, $userId, $from, $to);

        if (@$check['id'])
            return user_response(200, $check['id']);

        $result = (int)$archiveFeature->addDownloadRecord($request->id, $userId, $from, $to);

        task(new RecordTask($userId, $result))->low()->dispatch();

        return user_response(200, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/download/{uuid}', excludes: [JwtMiddleware::class, MobileMiddleware::class])]
    public function download(string $uuid, FileFeature $fileFeature): Response
    {
        $stream = $fileFeature->getFileStream($uuid);
        $info = $fileFeature->getFileInfo($uuid);

        return response()
            ->withHeader('Content-Type', 'video/mp4')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $info['filename'])
            ->withBody(stream($stream));
    }
}