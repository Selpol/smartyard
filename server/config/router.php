<?php declare(strict_types=1);

use Selpol\Controller\Internal\ActionController as InternalActionController;
use Selpol\Controller\Internal\FrsController as InternalFrsController;
use Selpol\Controller\Internal\SyncController as InternalSyncController;
use Selpol\Controller\Internal\PrometheusController as InternalPrometheusController;
use Selpol\Controller\Mobile\AddressController;
use Selpol\Controller\Mobile\ArchiveController;
use Selpol\Controller\Mobile\CallController;
use Selpol\Controller\Mobile\CameraController;
use Selpol\Controller\Mobile\FrsController;
use Selpol\Controller\Mobile\InboxController;
use Selpol\Controller\Mobile\IntercomController;
use Selpol\Controller\Mobile\PlogController;
use Selpol\Controller\Mobile\SubscriberController;
use Selpol\Controller\Mobile\UserController;
use Selpol\Middleware\InternalMiddleware;
use Selpol\Middleware\JwtMiddleware;
use Selpol\Middleware\MobileMiddleware;
use Selpol\Middleware\PrometheusMiddleware;
use Selpol\Middleware\RateLimitMiddleware;
use Selpol\Router\RouterConfigurator as RC;

return static function (RC $builder) {
    $builder->include(PrometheusMiddleware::class);

    $builder->group('/internal', static function (RC $builder) {
        $builder->include(InternalMiddleware::class);

        $builder->get('/test', [\Selpol\Controller\Internal\TestController::class, 'index']);

        $builder->group('/actions', static function (RC $builder) {
            $builder->get('/getSyslogConfig', [InternalActionController::class, 'getSyslogConfig']);

            $builder->post('/callFinished', [InternalActionController::class, 'callFinished']);
            $builder->post('/motionDetection', [InternalActionController::class, 'motionDetection']);
            $builder->post('/openDoor', [InternalActionController::class, 'openDoor']);
            $builder->post('/setRabbitGates', [InternalActionController::class, 'setRabbitGates']);
        });

        $builder->group('/frs', static function (RC $builder) {
            $builder->post('/callback', [InternalFrsController::class, 'callback']);
            $builder->get('/camshot/{id}', [InternalFrsController::class, 'camshot']);
        });

        $builder->group('/sync', static function (RC $builder) {
            $builder->post('/house', [InternalSyncController::class, 'getHouseGroup']);

            $builder->post('/subscriber', [InternalSyncController::class, 'addSubscriberGroup']);
            $builder->put('/subscriber', [InternalSyncController::class, 'updateSubscriberGroup']);
            $builder->delete('/subscriber', [InternalSyncController::class, 'deleteSubscriberGroup']);

            $builder->put('/flat', [InternalSyncController::class, 'updateFlatGroup']);

            $builder->post('/link', [InternalSyncController::class, 'addSubscriberToFlatGroup']);
            $builder->put('/link', [InternalSyncController::class, 'updateSubscriberToFlatGroup']);
            $builder->delete('/link', [InternalSyncController::class, 'deleteSubscriberFromFlatGroup']);
        });

        $builder->get('/prometheus', [InternalPrometheusController::class, 'index']);
    });

    $builder->group('/mobile', static function (RC $builder) {
        $builder->include(JwtMiddleware::class);
        $builder->include(MobileMiddleware::class);
        $builder->include(RateLimitMiddleware::class);

        $builder->group('/address', static function (RC $builder) {
            $builder->post('/getAddressList', [AddressController::class, 'getAddressList']);
            $builder->post('/registerQR', [AddressController::class, 'registerQR'], excludes: [MobileMiddleware::class]);

            $builder->post('/intercom', [IntercomController::class, 'intercom']);
            $builder->post('/openDoor', [IntercomController::class, 'openDoor']);
            $builder->post('/resetCode', [IntercomController::class, 'resetCode']);

            $builder->post('/plog', [PlogController::class, 'index']);
            $builder->get('/plogCamshot/{uuid}', [PlogController::class, 'camshot'], excludes: [JwtMiddleware::class, MobileMiddleware::class]);
            $builder->post('/plogDays', [PlogController::class, 'days']);
        });

        $builder->group('/cctv', static function (RC $builder) {
            $builder->post('/all', [CameraController::class, 'index']);
            $builder->get('/{cameraId}', [CameraController::class, 'show']);

            $builder->post('/events', [CameraController::class, 'events']);

            $builder->post('/recPrepare', [ArchiveController::class, 'prepare']);
            $builder->get('/download/{uuid}', [ArchiveController::class, 'download'], excludes: [JwtMiddleware::class, MobileMiddleware::class]);
        });

        $builder->group('/call', static function (RC $builder) {
            $builder->get('/camshot/{hash}', [CallController::class, 'camshot']);
            $builder->get('/live/{hash}', [CallController::class, 'live']);
        });

        $builder->group('/frs', static function (RC $builder) {
            $builder->get('/{flatId}', [FrsController::class, 'index']);
            $builder->post('/{eventId}', [FrsController::class, 'store']);
            $builder->delete('/', [FrsController::class, 'delete']);
        });

        $builder->group('/inbox', static function (RC $builder) {
            $builder->post('/read', [InboxController::class, 'read']);
            $builder->post('/unread', [InboxController::class, 'unread']);
        });

        $builder->group('/subscriber', static function (RC $builder) {
            $builder->get('/{flatId}', [SubscriberController::class, 'index']);
            $builder->post('/{flatId}', [SubscriberController::class, 'store']);
            $builder->delete('/{flatId}', [SubscriberController::class, 'delete']);
        });

        $builder->group('/user', static function (RC $builder) {
            $builder->post('/ping', [UserController::class, 'ping']);
            $builder->post('/registerPushToken', [UserController::class, 'registerPushToken']);
            $builder->post('/sendName', [UserController::class, 'sendName']);
        });
    });
};