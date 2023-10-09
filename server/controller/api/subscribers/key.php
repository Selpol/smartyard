<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Entity\Repository\House\HouseKeyRepository;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
    use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;

    /**
     * key method
     */
    class key extends api
    {
        public static function GET($params)
        {
            return self::SUCCESS('key', container(HouseKeyRepository::class)->findById($params['_id'])->toArrayMap([
                'house_rfid_id' => 'keyId',
                'rfid' => 'rfId',
                'access_type' => 'accessType',
                'access_to' => 'accessTo',
                'last_seen' => 'lastSeen',
                'comments' => 'comments'
            ]));
        }

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $keyId = $households->addKey($params["rfId"], $params["accessType"], $params["accessTo"], $params["comments"]);

            task(new IntercomAddKeyTask($params['rfId'], $params['accessTo']))->high()->dispatch();

            return api::ANSWER($keyId, ($keyId !== false) ? "key" : false);
        }

        public static function PUT($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->modifyKey($params["_id"], $params["comments"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $key = container(HouseKeyRepository::class)->findById($params['_id']);

            if (container(HouseKeyRepository::class)->delete($key)) {
                task(new IntercomDeleteKeyTask($key['rfId'], $key['accessTo']))->high()->dispatch();

                return self::ANSWER();
            }

            return self::ANSWER(false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}
