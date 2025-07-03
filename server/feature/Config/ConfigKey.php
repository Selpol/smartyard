<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

enum ConfigKey: string
{
    case Auth = 'auth';
    case AuthLogin = 'auth.login';

    case Debug = 'debug';
    case Log = 'log';
    case Handler = 'class';

    case Timeout = 'timeout';
    case Prepare = 'prepare';

    case Output = 'output';
    case OutputMap = 'output.map';
    case OutputInvert = 'output.invert';

    case Clean = 'clean';
    case CleanUnlockTime = 'clean.unlock_time';
    case CleanCallTimeout = 'clean.call_timeout';
    case CleanTalkTimeout = 'clean.talk_timeout';
    case CleanSos = 'clean.sos';
    case CleanConcierge = 'clean.concierge';
    case CleanNtp = 'clean.ntp';
    case CleanSyslog = 'clean.syslog';

    case Apartment = 'apartment';
    case ApartmentAnswer = 'apartment.answer';
    case ApartmnetQuiescent = 'apartment.quiescent';

    case Audio = 'audio';
    case AudioVolume = 'audio.volume';

    case Video = 'video';
    case VideoQuality = 'video.quality';
    case VideoPrimaryBitrate = 'video.primary_bitrate';
    case VideoSecondaryBitrate = 'video.secondary_bitrate';

    case Display = 'display';
    case DisplayTitle = 'display.title';

    case Cms = 'cms';
    case CmsValue = 'cms.value';

    case Sip = 'sip';
    case SipStream = 'sip.stream';
    case SipCall = 'sip.call';
    case SipDtmf = 'sip.dtmf';
    case SipSos = 'sip.sos';
    case SipNumber = 'sip.number';

    case Wicket = 'wicket';
    case WicketMode = 'wicket.mode';

    case Mifare = 'mifare';
    case MifareKey = 'mifare.key';
    case MifareSector = 'mifare.sector';
    case MifareCgi = 'mifare.cgi';

    case Screenshot = 'screenshot';

    public function key(): string
    {
        $segments = explode('.', $this->value);

        return $segments[array_key_last($segments)];
    }

    public function with(mixed ...$variables): string
    {
        $value = $this->value;

        foreach ($variables as $variable) {
            $value .= '.' . $variable;
        }

        return $value;
    }
}
