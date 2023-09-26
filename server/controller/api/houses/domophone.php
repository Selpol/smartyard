<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Service\DatabaseService;
    use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
    use Selpol\Validator\Rule;

    /**
     * domophone method
     */
    class domophone extends api
    {
        public static function GET($params)
        {
            $validate = validator($params, ['_id' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

            $households = container(HouseFeature::class);

            return api::ANSWER($households->getDomophone($validate['_id']));
        }

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $domophoneId = $households->addDomophone($params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["nat"], $params["comment"]);

            if ($domophoneId) {
                static::modifyIp($domophoneId, $params['url']);

                return api::ANSWER($domophoneId, 'domophoneId');
            }

            return api::ERROR('Домофон не добавлена');
        }

        public static function PUT($params)
        {
            $households = container(HouseFeature::class);

            $domophone = $households->getDomophone($params['_id']);

            if ($domophone) {
                $success = $households->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["firstTime"], $params["nat"], $params["locksAreOpen"], $params["comment"]);

                if ($success) {
                    if (array_key_exists('configure', $params) && $params['configure'])
                        task(new IntercomConfigureTask($params['_id']))->high()->dispatch();

                    if ($domophone['url'] !== $params['url'])
                        static::modifyIp($domophone['domophoneId'], $params['url']);
                }

                return api::ANSWER($success);
            }

            return api::ERROR('Домофон не найден');
        }

        public static function DELETE($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->deleteDomophone($params["_id"]);

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,PUT)",
            ];
        }

        private static function modifyIp(int $id, string $url): void
        {
            $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

            if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
                container(DatabaseService::class)->modify('UPDATE houses_domophones SET ip = :ip WHERE house_domophone_id = :id', ['ip' => $ip, 'id' => $id]);
        }
    }
}