<?php declare(strict_types=1);

namespace Selpol\Controller\Mobile;

use Psr\Http\Message\ResponseInterface;
use Selpol\Cache\RedisCache;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\Dvr\DvrAcquireRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrCommandRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrIdentifierRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrPreviewRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrScreenshotRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrVideoRequest;
use Selpol\Device\Ip\Dvr\Common\DvrCommand;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Middleware\RateLimitMiddleware;
use Throwable;

#[Controller('/mobile/dvr')]
readonly class DvrController extends RbtController
{
    #[Get(
        '/{id}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flat_id', 'house' => 'house_id'],
            BlockMiddleware::class => [BlockFeature::SERVICE_CCTV],
            BlockFlatMiddleware::class => ['flat' => 'flat_id', 'services' => [BlockFeature::SERVICE_CCTV]]
        ],
        excludes: [RateLimitMiddleware::class]
    )]
    public function identifier(DvrIdentifierRequest $request, BlockFeature $blockFeature, RedisCache $cache): ResponseInterface
    {
        $camera = DeviceCamera::findById($request->id);

        if (!$camera)
            return user_response(404, message: 'Камера не найдена');

        if (!$camera->checkAccessForSubscriber($this->getUser()->getOriginalValue(), $request->house_id, $request->flat_id, $request->entrance_id))
            return user_response(403, message: 'Доступа к камере нет');

        if (!is_null($request->house_id)) {
            $findFlatId = null;

            foreach ($this->getUser()->getOriginalValue()['flats'] as $flat) {
                if ($flat['addressHouseId'] == $request->house_id) {
                    $findFlatId = $flat['flatId'];

                    break;
                }
            }

            if (is_null($findFlatId))
                return user_response(404, message: 'Квартира не найдена');

            if (($block = $blockFeature->getFirstBlockForFlat($request->flat_id, [BlockFeature::SERVICE_CCTV])) !== null)
                return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));
        } else if (!is_null($request->flat_id) && ($block = $blockFeature->getFirstBlockForFlat($request->flat_id, [BlockFeature::SERVICE_CCTV])) !== null)
            return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));
        else if (($block = $blockFeature->getFirstBlockForSubscriber($this->getUser()->getIdentifier(), [BlockFeature::SERVICE_CCTV])) !== null)
            return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));

        $dvr = dvr($camera->dvr_server_id);

        if (!$dvr)
            return user_response(404, message: 'Устройство не найдено');

        $identifier = $dvr->identifier($camera, $request->time ?? time(), $this->getUser()->getIdentifier());

        if (!$identifier)
            return user_response(404, message: 'Идентификатор не найден');

        try {
            $cache->set('dvr:' . $identifier->value, [$identifier->start, $identifier->end, $request->id, $this->getUser()->getIdentifier()], 360);

            return user_response(data: [
                'identifier' => $identifier,

                'acquire' => $dvr->acquire(null, null),
                'capabilities' => $dvr->capabilities()
            ]);
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    #[Get('/acquire/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function acquire(DvrAcquireRequest $request, RedisCache $cache): ResponseInterface
    {
        try {
            $result = $this->process($cache, $request->id);

            if ($result instanceof ResponseInterface)
                return $result;

            /**
             * @var DvrIdentifier $identifier
             * @var DeviceCamera $camera
             * @var DvrDevice $dvr
             */
            list($identifier, $camera, $dvr) = $result;

            return user_response(data: $dvr->acquire($identifier, $camera));
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    #[Get('/screenshot/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function screenshot(DvrScreenshotRequest $request, RedisCache $cache): ResponseInterface
    {
        try {
            $result = $this->process($cache, $request->id);

            if ($result instanceof ResponseInterface)
                return $result;

            /**
             * @var DvrIdentifier $identifier
             * @var DeviceCamera $camera
             * @var DvrDevice $dvr
             */
            list($identifier, $camera, $dvr) = $result;

            $screenshot = $dvr->screenshot($identifier, $camera, $request->time);

            if ($screenshot)
                return response()->withHeader('Content-Type', 'image/jpeg')->withBody($screenshot);

            return response(204);
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    #[Get('/preview/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function preview(DvrPreviewRequest $request, RedisCache $cache): ResponseInterface
    {
        $result = $this->process($cache, $request->id);

        if ($result instanceof ResponseInterface)
            return $result;

        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $result;

        if (!$identifier->isNotExpired())
            return user_response(400, message: 'Токен доступа устарел');

        if (!is_null($request->time) && is_null($identifier->subscriber))
            return user_response(403, message: 'Доступ к предпросмотру архива запрещен');

        $preview = $dvr->preview($identifier, $camera, ['time' => $request->time]);

        if (!$preview)
            return user_response(404, message: 'Предпросмотр не доступен');

        return user_response(data: $preview);
    }

    #[Get('/video/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function video(DvrVideoRequest $request, RedisCache $cache): ResponseInterface
    {
        $result = $this->process($cache, $request->id);

        if ($result instanceof ResponseInterface)
            return $result;

        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $result;

        if (!$identifier->isNotExpired())
            return user_response(400, message: 'Токен доступа устарел');

        if ($request->stream == 'archive' && is_null($identifier->subscriber))
            return user_response(403, message: 'Доступ к архиву запрещен');

        $video = $dvr->video(
            $identifier,
            $camera,
            DvrContainer::from($request->container),
            DvrStream::from($request->stream),
            ['time' => $request->time, 'sub' => $request->sub, 'hw' => $request->hw]
        );

        if (!$video)
            return user_response(404, message: 'Видео не доступно');

        return user_response(data: $video);
    }

    #[Get('/command/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function command(DvrCommandRequest $request, RedisCache $cache): ResponseInterface
    {
        $result = $this->process($cache, $request->id);

        if ($result instanceof ResponseInterface)
            return $result;

        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $result;

        if (!$identifier->isNotExpired())
            return user_response(400, message: 'Токен доступа устарел');

        $command = $dvr->command(
            $identifier,
            $camera,
            DvrContainer::from($request->container),
            DvrStream::from($request->stream),
            DvrCommand::from($request->command),
            [
                'seek' => $request->seek,
                'speed' => $request->speed,

                'token' => $request->token,

                'from' => $request->from,
                'to' => $request->to
            ]
        );

        if (!$command)
            return user_response(404, message: 'Команда не доступна');
        else if ($command === true)
            return user_response();

        return user_response(data: $command);
    }

    private function process(RedisCache $cache, string $id): ResponseInterface|array
    {
        try {
            $value = $cache->get('dvr:' . $id);

            if (!$value)
                return user_response(404, message: 'Идентификатор не найден');

            $camera = DeviceCamera::findById($value[2]);

            if (!$camera)
                return user_response(404, message: 'Камера не найдена');

            $dvr = dvr($camera->dvr_server_id);

            if (!$dvr)
                return user_response(404, message: 'Устройство не найден');

            if (!$cache->set('dvr:' . $id, $value, 360))
                return user_response(404, message: 'Не удалось обновить идентификатор');

            return [new DvrIdentifier($id, $value[0], $value[1], $value[3]), $camera, $dvr];
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }
}