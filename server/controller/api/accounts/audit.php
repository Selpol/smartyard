<?php

namespace api\accounts;

use api\api;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Validator\Rule;

class audit extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'userId' => [Rule::id()],

            'auditableId' => [Rule::length()],
            'auditableType' => [Rule::length()],

            'eventIp' => [Rule::ipV4()],
            'eventType' => [Rule::length()],
            'eventTarget' => [Rule::length()],
            'eventCode' => [Rule::length()],
            'eventMessage' => [Rule::length(2048)],

            'page' => [Rule::int(), Rule::min(0), Rule::max()],
            'size' => [Rule::int(), Rule::min(0), Rule::max(1000)]
        ]);

        $audits = container(AuditFeature::class)->audits($validate['userId'], $validate['auditableId'], $validate['auditableType'], $validate['eventIp'], $validate['eventType'], $validate['eventTarget'], $validate['eventCode'], $validate['eventMessage'], $validate['page'], $validate['size']);

        if ($audits)
            return api::SUCCESS('audits', $audits);

        return api::SUCCESS('audits', []);
    }

    public static function index(): array
    {
        return ['GET' => '#same(accounts,audit,GET)'];
    }
}