<?php

use logger\Logger;

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFile($uuid);

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid]);

if ($img) {
    $content_type = "image/jpeg";
    $meta_data = $files->getFileMetadata($uuid);

    Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'meta' => $meta_data]);

    if (isset($meta_data->contentType))
        $content_type = $meta_data->contentType;

    $image = imagecreatefromstring(stream_get_contents($img['stream']));

    if ($image) {
        Logger::channel('plog')->debug('plogCamshot send', ['uuid' => $u]);

        header("Content-Type: $content_type");

        imagejpeg($image);
        imagedestroy($image);

        exit;
    }
}
