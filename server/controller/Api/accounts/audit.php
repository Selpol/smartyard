<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\AuditRepository;

readonly class audit extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'userId' => rule()->int()->clamp(0),

            'auditableId' => rule()->string()->max(1024),
            'auditableType' => rule()->string()->max(1024),

            'eventIp' => rule()->ipV4(),
            'eventType' => rule()->string()->max(1024),
            'eventTarget' => rule()->string()->max(1024),
            'eventCode' => rule()->string()->max(1024),
            'eventMessage' => rule()->string()->max(2048),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        return self::SUCCESS('audits', container(AuditRepository::class)->fetchPage(
            $validate['page'],
            $validate['size'],
            criteria()
                ->equal('user_id', $validate['userId'])
                ->equal('auditable_id', $validate['auditableId'])
                ->equal('auditable_type', $validate['auditableType'])
                ->equal('event_ip', $validate['eventIp'])
                ->equal('event_type', $validate['eventType'])
                ->like('event_target', $validate['eventTarget'])
                ->equal('event_code', $validate['eventCode'])
                ->like('event_message', $validate['eventMessage'])
                ->desc('created_at')
        ));
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь] Получить список действий'];
    }
}