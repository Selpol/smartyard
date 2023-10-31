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
use Selpol\Router\RouterConfigurator;

return static function (RouterConfigurator $configurator) {
    $configurator->include(PrometheusMiddleware::class);

    $configurator->group('/internal', static function (RouterConfigurator $configurator) {
        $configurator->include(InternalMiddleware::class);

        $configurator->group('/actions', static function (RouterConfigurator $configurator) {
            $configurator->post('/callFinished', [InternalActionController::class, 'callFinished']);
            $configurator->post('/motionDetection', [InternalActionController::class, 'motionDetection']);
            $configurator->post('/openDoor', [InternalActionController::class, 'openDoor']);
            $configurator->post('/setRabbitGates', [InternalActionController::class, 'setRabbitGates']);
        });

        $configurator->group('/frs', static function (RouterConfigurator $configurator) {
            $configurator->post('/callback', [InternalFrsController::class, 'callback']);
            $configurator->get('/camshot/{id}', [InternalFrsController::class, 'camshot']);
        });

        $configurator->group('/sync', static function (RouterConfigurator $configurator) {
            $configurator->post('/house', [InternalSyncController::class, 'getHouseGroup']);

            $configurator->post('/subscriber', [InternalSyncController::class, 'addSubscriberGroup']);
            $configurator->put('/subscriber', [InternalSyncController::class, 'updateSubscriberGroup']);
            $configurator->delete('/subscriber', [InternalSyncController::class, 'deleteSubscriberGroup']);

            $configurator->put('/flat', [InternalSyncController::class, 'updateFlatGroup']);

            $configurator->post('/link', [InternalSyncController::class, 'addSubscriberToFlatGroup']);
            $configurator->put('/link', [InternalSyncController::class, 'updateSubscriberToFlatGroup']);
            $configurator->delete('/link', [InternalSyncController::class, 'deleteSubscriberFromFlatGroup']);
        });

        $configurator->get('/prometheus', [InternalPrometheusController::class, 'index']);
    });

    $configurator->group('/mobile', static function (RouterConfigurator $configurator) {
        $configurator->include(JwtMiddleware::class);
        $configurator->include(MobileMiddleware::class);
        $configurator->include(RateLimitMiddleware::class);

        $configurator->group('/address', static function (RouterConfigurator $configurator) {
            $configurator->post('/getAddressList', [AddressController::class, 'getAddressList']);
            $configurator->post('/registerQR', [AddressController::class, 'registerQR'], excludes: [MobileMiddleware::class]);

            $configurator->post('/intercom', [IntercomController::class, 'intercom']);
            $configurator->post('/openDoor', [IntercomController::class, 'openDoor']);
            $configurator->post('/resetCode', [IntercomController::class, 'resetCode']);

            $configurator->post('/plog', [PlogController::class, 'index']);
            $configurator->get('/plogCamshot/{uuid}', [PlogController::class, 'camshot'], excludes: [JwtMiddleware::class, MobileMiddleware::class]);
            $configurator->post('/plogDays', [PlogController::class, 'days']);
        });

        $configurator->group('/cctv', static function (RouterConfigurator $configurator) {
            $configurator->post('/all', [CameraController::class, 'index']);
            $configurator->get('/{cameraId}', [CameraController::class, 'show']);

            $configurator->post('/events', [CameraController::class, 'events']);

            $configurator->post('/recPrepare', [ArchiveController::class, 'prepare']);
            $configurator->get('/download/{uuid}', [ArchiveController::class, 'download'], excludes: [JwtMiddleware::class, MobileMiddleware::class]);
        });

        $configurator->group('/call', static function (RouterConfigurator $configurator) {
            $configurator->get('/camshot/{hash}', [CallController::class, 'camshot']);
            $configurator->get('/live/{hash}', [CallController::class, 'live']);
        });

        $configurator->group('/frs', static function (RouterConfigurator $configurator) {
            $configurator->get('/{flatId}', [FrsController::class, 'index']);
            $configurator->post('/{eventId}', [FrsController::class, 'store']);
            $configurator->delete('/', [FrsController::class, 'delete']);
        });

        $configurator->group('/inbox', static function (RouterConfigurator $configurator) {
            $configurator->post('/read', [InboxController::class, 'read']);
            $configurator->post('/unread', [InboxController::class, 'unread']);
        });

        $configurator->group('/subscriber', static function (RouterConfigurator $configurator) {
            $configurator->get('/{flatId}', [SubscriberController::class, 'index']);
            $configurator->post('/{flatId}', [SubscriberController::class, 'store']);
            $configurator->delete('/{flatId}', [SubscriberController::class, 'delete']);
        });

        $configurator->group('/user', static function (RouterConfigurator $configurator) {
            $configurator->post('/ping', [UserController::class, 'ping']);
            $configurator->post('/registerPushToken', [UserController::class, 'registerPushToken']);
            $configurator->post('/sendName', [UserController::class, 'sendName']);
        });
    });
};