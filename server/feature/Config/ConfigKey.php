<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

enum ConfigKey: string
{
    case Auto = 'auto';
    case AutoIs1 = 'auto.is1';
    case AutoIs5 = 'auto.is5';
    case AutoDks = 'auto.dks';
    case AutoDs = 'auto.ds';
    case AutoHik = 'auto.hik';

    case AutoCamera = 'auto.camera';

    case AutoTemplate = 'auto.template';
    case AutoTemplateDvr = 'auto.template.dvr';
    case AutoTemplatePrimary = 'auto.template.primary';
    case AutoTemplateSecondary = 'auto.template.secondary';

    case Auth = 'auth';
    case AuthLogin = 'auth.login';
    case AuthPassword = 'auth.password';

    case Debug = 'debug';
    case Log = 'log';
    case Handler = 'class';

    case Timeout = 'timeout';
    case Prepare = 'prepare';
    case Check = 'check';

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
    case VideoRate = 'video.rate';
    case VideoRateOffset = 'video.rate.offset';

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

    case Gsm = 'gsm';

    case GsmAdd = 'gsm.{gms}.add';
    case GsmRemove = 'gsm.{gms}.remove';

    case Screenshot = 'screenshot';

    public function last(): string
    {
        $segments = explode('.', $this->value);

        return $segments[array_key_last($segments)];
    }

    public function with(array $variables): string
    {
        $value = $this->value;

        foreach ($variables as $key => $variable) {
            $value = str_replace('{' . $key . '}', $variable, $value);
        }

        return $value;
    }

    public function with_end(mixed ...$variables): string
    {
        $value = $this->value;

        foreach ($variables as $variable) {
            $value .= '.' . $variable;
        }

        return $value;
    }

}
