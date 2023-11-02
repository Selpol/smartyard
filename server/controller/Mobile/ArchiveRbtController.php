<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
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
readonly class ArchiveRbtController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/recPrepare')]
    public function prepare(ServerRequestInterface $request): Response
    {
        $userId = $this->getUser()->getIdentifier();

        $validate = validator($request->getParsedBody(), [
            'id' => rule()->id(),
            'from' => rule()->required()->nonNullable(),
            'to' => rule()->required()->nonNullable()
        ]);

        $cameraId = $validate['id'];

        date_default_timezone_set('Europe/Moscow');

        $from = strtotime($validate['from']);
        $to = strtotime($validate['to']);

        if (!$from || !$to)
            return user_response(400, message: 'Неверный формат данных');

        $archive = container(ArchiveFeature::class);

        // проверяем, не был ли уже запрошен данный кусок из архива.
        $check = $archive->checkDownloadRecord($cameraId, $userId, $from, $to);

        if (@$check['id'])
            return user_response(200, $check['id']);

        $result = (int)$archive->addDownloadRecord($cameraId, $userId, $from, $to);

        task(new RecordTask($userId, $result))->low()->dispatch();

        return user_response(200, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/download/{uuid}', excludes: [JwtMiddleware::class, MobileMiddleware::class])]
    public function download(string $uuid): Response
    {
        $file = container(FileFeature::class);

        $stream = $file->getFileStream($uuid);
        $info = $file->getFileInfo($uuid);

        return response()
            ->withHeader('Content-Type', 'video/mp4')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $info['filename'])
            ->withBody(stream($stream));
    }
}