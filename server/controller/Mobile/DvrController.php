<?php declare(strict_types=1);

namespace Selpol\Controller\Mobile;

use Selpol\Device\Ip\Dvr\Common\DvrOutput;
use Selpol\Device\Ip\Dvr\Common\DvrStreamer;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\Dvr\DvrCommandRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrEventRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrIdentifierRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrPreviewRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrScreenshotRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrTimelineRequest;
use Selpol\Controller\Request\Mobile\Dvr\DvrVideoRequest;
use Selpol\Device\Ip\Dvr\Common\DvrCommand;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Feature\Streamer\Stream;
use Selpol\Feature\Streamer\StreamerFeature;
use Selpol\Feature\Streamer\StreamInput;
use Selpol\Feature\Streamer\StreamOutput;
use Selpol\Feature\Streamer\StreamTransport;
use Selpol\Framework\Kernel\Exception\KernelException;
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
readonly class DvrController extends MobileRbtController
{
    #[Get(
        '/{id}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flat_id', 'house' => 'house_id'],
            BlockMiddleware::class => [BlockFeature::SERVICE_CCTV],
            BlockFlatMiddleware::class => ['flat' => 'flat_id', 'house' => 'house_id', 'services' => [BlockFeature::SERVICE_CCTV]]
        ],
        excludes: [RateLimitMiddleware::class]
    )]
    public function identifier(DvrIdentifierRequest $request, BlockFeature $blockFeature): ResponseInterface
    {
        $camera = DeviceCamera::findById($request->id);

        if (!$camera instanceof DeviceCamera) {
            return user_response(404, message: 'Камера не найдена');
        }

        if (!$camera->checkAccessForSubscriber($this->getUser()->getOriginalValue(), $request->house_id, $request->flat_id, $request->entrance_id)) {
            return user_response(403, message: 'Доступа к камере нет');
        }

        if (!is_null($request->house_id)) {
            $findFlatId = null;
            foreach ($this->getUser()->getOriginalValue()['flats'] as $flat) {
                if ($flat['addressHouseId'] == $request->house_id) {
                    $findFlatId = $flat['flatId'];

                    break;
                }
            }

            if (is_null($findFlatId)) {
                return user_response(404, message: 'Квартира не найдена');
            }

            if (($block = $blockFeature->getFirstBlockForFlat($findFlatId, [BlockFeature::SERVICE_CCTV])) instanceof FlatBlock) {
                return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));
            }
        } elseif (!is_null($request->flat_id) && ($block = $blockFeature->getFirstBlockForFlat($request->flat_id, [BlockFeature::SERVICE_CCTV])) instanceof FlatBlock) {
            return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));
        } elseif (($block = $blockFeature->getFirstBlockForUser([BlockFeature::SERVICE_CCTV])) instanceof SubscriberBlock) {
            return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));
        }

        $dvr = dvr($camera->dvr_server_id);

        if (!$dvr instanceof DvrDevice) {
            return user_response(404, message: 'Устройство не найдено');
        }

        $identifier = $dvr->identifier($camera, $request->time ?? time(), $this->getUser()->getIdentifier());

        if (!$identifier instanceof DvrIdentifier) {
            return user_response(404, message: 'Идентификатор не найден');
        }

        try {
            return user_response(data: [
                'identifier' => ['value' => $identifier->toToken(), 'start' => $identifier->start, 'end' => $identifier->end],

                'type' => $dvr->server->type,

                'capabilities' => $dvr->capabilities()
            ]);
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    #[Get('/screenshot/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function screenshot(DvrScreenshotRequest $request): ResponseInterface
    {
        try {
            /**
             * @var DvrIdentifier $identifier
             * @var DeviceCamera $camera
             * @var DvrDevice $dvr
             */
            list($identifier, $camera, $dvr) = $this->process($request->id);

            $screenshot = $dvr->screenshot($identifier, $camera, $request->time);

            if ($screenshot) {
                return response()->withHeader('Content-Type', 'image/jpeg')->withBody($screenshot);
            }

            return response(204);
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    #[Get('/preview/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function preview(DvrPreviewRequest $request): ResponseInterface
    {
        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $this->process($request->id);

        if (!is_null($request->time) && is_null($identifier->subscriber)) {
            return user_response(403, message: 'Доступ к предпросмотру архива запрещен');
        }

        $preview = $dvr->preview($identifier, $camera, ['time' => $request->time]);

        if (!$preview) {
            return user_response(404, message: 'Предпросмотр не доступен');
        }

        return user_response(data: $preview);
    }

    #[Get('/video/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class, RateLimitMiddleware::class])]
    public function video(DvrVideoRequest $request, StreamerFeature $feature): ResponseInterface
    {
        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $this->process($request->id);

        if (is_null($identifier->subscriber)) {
            if ($request->stream == DvrStream::CAMERA->value) {
                return user_response(403, message: 'Доступ к видео с камеры запрещен');
            } else if ($request->stream == DvrStream::ARCHIVE->value) {
                return user_response(403, message: 'Доступ к архиву запрещен');
            }
        }

        if ($request->container == DvrContainer::STREAMER_RTC->value && $request->stream == DvrStream::CAMERA->value) {
            $server = $feature->random();

            $stream = new Stream($server, $server->id . '-' . uniqid(more_entropy: true));
            $stream->source($camera->stream)->input(StreamInput::RTSP)->output(StreamOutput::RTC)->latency(25)->transport(StreamTransport::UDP);

            $feature->stream($stream);

            return user_response(data: new DvrOutput(
                DvrContainer::STREAMER_RTC,
                new DvrStreamer($stream->getServer()->url, $stream->getToken(), $stream->getOutput())
            ));
        }

        $video = $dvr->video(
            $identifier,
            $camera,
            DvrContainer::from($request->container),
            DvrStream::from($request->stream),
            ['time' => $request->time, 'sub' => $request->sub, 'hw' => $request->hw]
        );

        if (!$video) {
            return user_response(404, message: 'Видео не доступно');
        }

        return user_response(data: $video);
    }

    #[Get('/timeline/{id}')]
    public function timeline(DvrTimelineRequest $request): ResponseInterface
    {
        $result = $this->process($request->id);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $result;

        $timeline = $dvr->timeline($identifier, $camera, ['token' => $request->token]);

        if (!$timeline) {
            return user_response(404, message: 'Таймлайн не найден');
        }

        return user_response(data: $timeline);
    }

    #[Get('/event/{id}')]
    public function event(DvrEventRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature): ResponseInterface
    {
        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $this->process($request->id);

        $dvrEvents = $dvr->event($identifier, $camera, ['after' => $request->after, 'before' => $request->before, 'token' => $request->token]);

        $domophoneId = $houseFeature->getDomophoneIdByEntranceCameraId($camera->camera_id);

        if (is_null($domophoneId)) {
            return user_response(data: $dvrEvents);
        }

        if ($this->getUser()->getOriginalValue()['role'] == 1) {
            $intercomEvents = $plogFeature->getEventsByIntercom($domophoneId, $request->after, $request->before);

            if (is_array($intercomEvents)) {
                $intercomEvents = array_map(static fn(array $item): array => [$item['date'] - 10, $item['date'] + 10, $item['event']], $intercomEvents);
                $events = array_merge($dvrEvents, $intercomEvents);

                usort($events, static function (array $a, array $b): int {
                    if ($a[0] == $b[0]) {
                        return 0;
                    }

                    return $a[0] > $b[1] ? 1 : -1;
                });

                return user_response(data: $events);
            }

            return user_response(data: $dvrEvents);
        }

        $flats = array_filter(
            array_map(static fn(array $item): array => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $this->getUser()->getOriginalValue()['flats']),
            static function (array $flat) use ($houseFeature): bool {
                $plog = $houseFeature->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == PlogFeature::ACCESS_ALL || $plog == PlogFeature::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0) {
            return user_response(data: $dvrEvents);
        }

        $intercomEvents = $plogFeature->getEventByFlatsAndIntercom($flatsId, $domophoneId, $request->after, $request->before);

        if ($intercomEvents) {
            $intercomEvents = array_map(static fn(array $item): array => [$item['date'] - 10, $item['date'] + 10, $item['event']], $intercomEvents);
            $events = array_merge($dvrEvents, $intercomEvents);

            usort($events, static function (array $a, array $b): int {
                if ($a[0] == $b[0]) {
                    return 0;
                }

                return $a[0] > $b[1] ? 1 : -1;
            });

            return user_response(data: $events);
        }

        return user_response(data: $dvrEvents);
    }

    #[Get('/command/{id}', excludes: [RateLimitMiddleware::class])]
    public function command(DvrCommandRequest $request): ResponseInterface
    {
        /**
         * @var DvrIdentifier $identifier
         * @var DeviceCamera $camera
         * @var DvrDevice $dvr
         */
        list($identifier, $camera, $dvr) = $this->process($request->id);

        $command = $dvr->command(
            $identifier,
            $camera,
            DvrContainer::from($request->container),
            DvrStream::from($request->stream),
            DvrCommand::from($request->command),
            ['seek' => $request->seek, 'speed' => $request->speed, 'token' => $request->token, 'from' => $request->from, 'to' => $request->to]
        );

        if (!$command) {
            return user_response(404, message: 'Команда не доступна');
        }

        if ($command === true) {
            return user_response();
        }

        return user_response(data: $command);
    }

    /**
     * @param string $token
     * @return array
     * @throws KernelException
     */
    private function process(string $token): array
    {
        $identifier = DvrIdentifier::fromToken($token);

        $camera = DeviceCamera::findById($identifier->camera);

        if (!$camera instanceof DeviceCamera) {
            throw new KernelException('Камера не найдена');
        }

        $dvr = dvr($identifier->dvr);

        if (!$dvr instanceof DvrDevice) {
            throw new KernelException('Устройство не найдено');
        }

        return [$identifier, $camera, $dvr];
    }
}