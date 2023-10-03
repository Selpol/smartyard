<?php

namespace Selpol\Feature\Geo\DaData;

use Selpol\Feature\Geo\GeoFeature;
use Throwable;

class DaDataGeoFeature extends GeoFeature
{
    public function suggestions(string $search): bool|array
    {
        $geo = config('feature.geo');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token {$geo["token"]}"
        ]);

        curl_setopt($curl, CURLOPT_POST, 1);

        if (@$geo["locations"])
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["query" => $search, "locations" => $geo["locations"]]));
        else
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["query" => $search]));

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);

        $result_raw = curl_exec($curl);
        $result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result = json_decode($result_raw, true);

        curl_close($curl);

        if ($result_code >= 200 && $result_code < 400) {
            for ($i = 0; $i < count($result["suggestions"]); $i++) {
                if ((int)$result["suggestions"][$i]["data"]["fias_level"] === 8 || ((int)$result["suggestions"][$i]["data"]["fias_level"] === -1 && $result["suggestions"][$i]["data"]["house"])) {
                    try {
                        $this->getRedis()->getConnection()->setex("house_" . $result["suggestions"][$i]["data"]["house_fias_id"], 7 * 24 * 60 * 60, json_encode($result["suggestions"][$i]));
                    } catch (Throwable) {
                    }
                }
            }

            return $result["suggestions"];
        } else return false;
    }
}