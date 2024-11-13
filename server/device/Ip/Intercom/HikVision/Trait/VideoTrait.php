<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use Selpol\Device\Ip\Intercom\Setting\Video\VideoDetection;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoDisplay;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoEncoding;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoOverlay;

trait VideoTrait
{
    public function getVideoEncoding(): VideoEncoding
    {
        $response = $this->get('/ISAPI/Streaming/channels/101');

        $video = $response['Video'];

        return new VideoEncoding($video['videoResolutionWidth'] . 'x' . $video['videoResolutionHeight'], intval($video['constantBitRate']), 512);
    }

    public function getVideoDetection(): VideoDetection
    {
        return new VideoDetection(false, null, null, null, null);
    }

    public function getVideoDisplay(): VideoDisplay
    {
        return new VideoDisplay('');
    }

    public function getVideoOverlay(): VideoOverlay
    {
        $response = $this->get('/ISAPI/System/Video/inputs/channels/1');

        return new VideoOverlay(array_key_exists('VideoInputChannel', $response) && is_string($response['VideoInputChannel']['name']) ? $response['VideoInputChannel']['name'] : null);
    }

    public function setVideoEncoding(VideoEncoding $videoEncoding): void
    {
        list($width, $height) = explode('x', $videoEncoding->quality ?? '1920x1080');

        $this->put('/ISAPI/Streaming/channels/101', "<StreamingChannel><id>101</id><channelName>Camera 01</channelName><enabled>true</enabled><Transport><ControlProtocolList><ControlProtocol><streamingTransport>RTSP</streamingTransport></ControlProtocol><ControlProtocol><streamingTransport>HTTP</streamingTransport></ControlProtocol></ControlProtocolList><Security><enabled>true</enabled></Security></Transport><Video><enabled>true</enabled><videoInputChannelID>1</videoInputChannelID><videoCodecType>H.264</videoCodecType><videoScanType>progressive</videoScanType><videoResolutionWidth>" . $width . "</videoResolutionWidth><videoResolutionHeight>" . $height . "</videoResolutionHeight><videoQualityControlType>CBR</videoQualityControlType><constantBitRate>" . $videoEncoding->primaryBitrate . '</constantBitRate><fixedQuality>60</fixedQuality><maxFrameRate>2500</maxFrameRate><keyFrameInterval>2000</keyFrameInterval><snapShotImageType>JPEG</snapShotImageType><GovLength>50</GovLength></Video><AudioLevels><enabled>true</enabled><audioInputChannelID>1</audioInputChannelID><audioCompressionType>G.711ulaw</audioCompressionType></AudioLevels></StreamingChannel>', ['Content-Type' => 'application/xml']);
    }

    public function setVideoDetection(VideoDetection $videoDetection): void
    {
    }

    public function setVideoDisplay(VideoDisplay $videoDisplay): void
    {
    }

    public function setVideoOverlay(VideoOverlay $videoOverlay): void
    {
        $this->put('/ISAPI/System/Video/inputs/channels/1', "<VideoInputChannel><id>1</id><inputPort>1</inputPort><name>" . $videoOverlay->title . "</name></VideoInputChannel>", ['Content-Type' => 'application/xml']);
        $this->put('/ISAPI/System/Video/inputs/channels/1/overlays', '<VideoOverlay><DateTimeOverlay><enabled>true</enabled><positionY>540</positionY><positionX>0</positionX><dateStyle>MM-DD-YYYY</dateStyle><timeStyle>24hour</timeStyle><displayWeek>true</displayWeek></DateTimeOverlay><channelNameOverlay><enabled>true</enabled><positionY>700</positionY><positionX>0</positionX></channelNameOverlay></VideoOverlay>', ['Content-Type' => 'application/xml']);
    }
}