<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\StreamerDeleteRequest;
use Selpol\Controller\Request\Admin\StreamerRequest;
use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Feature\Streamer\ApiStream;
use Selpol\Feature\Streamer\StreamerFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Стример
 */
#[Controller('/admin/streamer')]
readonly class StreamerController extends AdminRbtController
{
    /**
     * Получить потоки со стримера
     */
    #[Get('/{id}')]
    public function index(int $id, StreamerFeature $feature): ResponseInterface
    {
        $server = StreamerServer::findById($id, setting: setting()->nonNullable());

        return self::success($feature->listStream($server));
    }

    /**
     * Добавить поток на стример
     */
    #[Post('/{id}')]
    public function store(StreamerRequest $request): ResponseInterface
    {
        $server = StreamerServer::findById($request->id, setting: setting()->nonNullable());

        $stream = new ApiStream($request->stream_id, $request->input, $request->input_type, $request->output_type, null, null);

        if (container(StreamerFeature::class)->addStream($server, $stream)) {
            return self::success();
        }

        return self::error('Не удалось добавить поток на стример', 404);
    }

    /**
     * Обновить поток на стримере
     */
    #[Put('/{id}')]
    public function update(StreamerRequest $request): ResponseInterface
    {
        $server = StreamerServer::findById($request->id, setting: setting()->nonNullable());

        $stream = new ApiStream($request->stream_id, $request->input, $request->input_type, $request->output_type, null, null);

        if (container(StreamerFeature::class)->updateStream($server, $stream)) {
            return self::success();
        }

        return self::error('Не удалось обновить поток на стримере', 404);
    }

    /**
     * Удалить поток на стримере
     */
    #[Delete('/{id}')]
    public function delete(StreamerDeleteRequest $request): ResponseInterface
    {
        $server = StreamerServer::findById($request->id, setting: setting()->nonNullable());

        if (container(StreamerFeature::class)->deleteStream($server, $request->stream_id)) {
            return self::success();
        }

        return self::error('Не удалось удалить поток на стримере', 404);
    }
}