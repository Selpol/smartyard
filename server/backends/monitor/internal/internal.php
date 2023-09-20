<?php

namespace backends\monitor\internal;

use backends\monitor\monitor;
use Throwable;

class internal extends monitor
{
    public function ping(int $id): bool
    {
        try {
            return redis_cache('monitor:' . $id . ':ping', static function () use ($id) {
                $domophone = backend('households')->getDomophone($id);

                if (!$domophone)
                    return false;

                $intercom = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

                if (!$intercom)
                    return false;

                return $intercom->ping();
            }, 30);
        } catch (Throwable) {
            return false;
        }
    }

    public function sip(int $id): bool
    {
        try {
            return redis_cache('monitor:' . $id . ':sip', static function () use ($id) {
                $domophone = backend('households')->getDomophone($id);

                if (!$domophone)
                    return false;

                $intercom = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

                if (!$intercom)
                    return false;

                return $intercom->getSipStatus();
            }, 30);
        } catch (Throwable) {
            return false;
        }
    }
}