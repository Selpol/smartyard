<?php

/**
 * backends monitoring namespace.
 */

namespace backends\monitoring;

/**
 * simple monitoring class.
 */
class simple extends monitoring
{
    /**
     * @inheritDoc
     */
    public function deviceStatus($deviceType, $deviceId)
    {
        switch ($deviceType) {
            case 'camera':
            case 'domophone':
                return ["status" => "unknown", "message" => i18n("monitoring.unknown")];
        }

        return ["status" => "unknown", "message" => i18n("monitoring.unknown")];
    }
}