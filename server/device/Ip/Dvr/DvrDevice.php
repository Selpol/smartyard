<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr;

use Psr\Http\Message\StreamInterface;
use Selpol\Device\Ip\Dvr\Common\DvrArchive;
use Selpol\Device\Ip\Dvr\Common\DvrCommand;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Selpol\Device\Ip\Dvr\Common\DvrOnline;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class DvrDevice extends IpDevice
{
    public DvrModel $model;
    public DvrServer $server;

    public function __construct(Uri $uri, string $login, #[SensitiveParameter] string $password, DvrModel $model, DvrServer $server)
    {
        $this->login = $login;

        parent::__construct($uri, $password);

        $this->model = $model;
        $this->server = $server;
    }

    public function getCameras(): array
    {
        return [];
    }

    public function getCameraId(string $query): ?string
    {
        return null;
    }

    public function acquire(?DvrIdentifier $identifier, ?DeviceCamera $camera): int
    {
        return 0;
    }

    public function capabilities(): array
    {
        return [
            'poster' => false,
            'preview' => false,

            'online' => false,
            'archive' => false,

            'command' => [],
            'speed' => []
        ];
    }

    public function identifier(DeviceCamera $camera, int $time, ?int $subscriberId): ?DvrIdentifier
    {
        return null;
    }

    public function screenshot(DvrIdentifier $identifier, DeviceCamera $camera, ?int $time): ?StreamInterface
    {
        return null;
    }

    /**
     * $arguments = [
     *   "time" => "Время предпросмотра"
     * ]
     *
     * @param DvrIdentifier $identifier
     * @param DeviceCamera $camera
     * @param array $arguments
     * @return string|null
     */
    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        return null;
    }

    /**
     *  $arguments = [
     *    "time" => "Время видео"
     *  ]
     *
     * @param DvrIdentifier $identifier
     * @param DeviceCamera $camera
     * @param DvrContainer $container
     * @param DvrStream $stream
     * @param array $arguments
     * @return DvrArchive|DvrOnline|string|null
     */
    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): DvrArchive|DvrOnline|string|null
    {
        return null;
    }

    public function command(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, DvrCommand $command, array $arguments): mixed
    {
        return null;
    }
}