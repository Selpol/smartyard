<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Task\Tasks\Intercom\Flat\IntercomDeleteFlatTask;
    use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

    /**
     * house method
     */
    class flat extends api
    {
        public static function GET($params)
        {
            $flatId = @$params['_id'];

            if (!isset($flatId))
                return api::ERROR('Неверный формат данных');

            $flat = backend('households')->getFlat($flatId);

            return api::ANSWER($flat, ($flat !== false) ? 'flat' : 'notAcceptable');
        }

        public static function POST($params)
        {
            $households = backend("households");

            $flatId = $households->addFlat($params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], $params["manualBlock"], $params["adminBlock"], $params["openCode"], $params["plog"], $params["autoOpen"], $params["whiteRabbit"], $params["sipEnabled"], $params["sipPassword"]);

            if ($flatId)
                high_dispatch(new IntercomSyncFlatTask($flatId, true));

            return api::ANSWER($flatId, ($flatId !== false) ? "flatId" : "notAcceptable");
        }

        public static function PUT($params)
        {
            $households = backend("households");

            $success = $households->modifyFlat($params["_id"], $params);

            if ($success)
                high_dispatch(new IntercomSyncFlatTask($params['_id'], false));

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $households = backend("households");

            $flat = $households->getFlat($params['_id']);

            if ($flat) {
                $entrances = $households->getEntrances('flatId', $flat['flatId']);

                $success = $households->deleteFlat($params["_id"]);

                if ($success)
                    high_dispatch(new IntercomDeleteFlatTask($flat['flatId'], array_map(static fn(array $entrance) => [$entrance['apartment'], $entrance['domophoneId']], $entrances)));

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            return api::ERROR('Дом не найден');
        }

        public static function index()
        {
            return [
                'GET' => '#same(addresses,house,GET)',
                "POST" => "#same(addresses,house,PUT)",
                "PUT" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,PUT)",
            ];
        }
    }
}