<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\ArchivePrepareRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileStorage;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Task\Tasks\RecordTask;
use Throwable;

#[Controller('/mobile/cctv', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_CCTV, BlockFeature::SUB_SERVICE_ARCHIVE]])]
readonly class ArchiveController extends MobileRbtController
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

        if ($from === 0 || $from === false || ($to === 0 || $to === false)) {
            return user_response(400, message: 'Неверный формат данных');
        }

        if ($to - $from > 1800) {
            return user_response(400, message: 'Нельзя выгрузить отрезок из архива длинее 30 минут');
        }

        $camera = DeviceCamera::findById($request->id);

        if (!$camera instanceof DeviceCamera || !$camera->checkAllAccessForSubscriber($this->getUser()->getOriginalValue())) {
            return user_response(404, message: 'Камера не найдена');
        }

        // проверяем, не был ли уже запрошен данный кусок из архива.
        $check = $archiveFeature->checkDownloadRecord($request->id, $userId, $from, $to);

        if (is_array($check) && array_key_exists('id', $check)) {
            return user_response(200, $check['id']);
        }

        $result = (int) $archiveFeature->addDownloadRecord($request->id, $userId, $from, $to);

        task(new RecordTask($userId, $result))->queue('record')->dispatch();

        return user_response(200, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/download/{uuid}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, BlockMiddleware::class])]
    public function download(string $uuid, FileFeature $fileFeature): Response
    {
        try {
            $file = $fileFeature->getFile($uuid, FileStorage::Archive);

            return response()
                ->withHeader('Content-Type', 'video/mp4')
                ->withHeader('Content-Disposition', 'attachment; filename=' . $file->info->filename)
                ->withBody($file->stream);
        } catch (Throwable) {
            return response()
                ->withHeader('Content-Type', 'charset=utf-8')
                ->withBody(stream('Не удалось получить доступ к отрезку архива'));
        }
    }
}