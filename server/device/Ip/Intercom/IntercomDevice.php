<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigResolver;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class IntercomDevice extends IpDevice
{
    public readonly ConfigResolver $resolver;

    public readonly bool $mifare;

    public readonly ?string $mifareKey;
    
    public readonly ?int $mifareSector;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, public IntercomModel $model, public DeviceIntercom $intercom, ConfigResolver $resolver)
    {
        parent::__construct($uri, $password);

        $this->resolver = $resolver;

        match ($this->resolver->string('auth', 'basic')) {
            "any_safe" => $this->clientOption->anySafe($this->login, $password),
            "basic" => $this->clientOption->basic($this->login, $password),
            "digest" => $this->clientOption->digest($this->login, $password),
        };

        if (!$this->debug) {
            $this->debug = $this->resolver->bool('debug', false);
        }

        if ($this->resolver->bool('mifare', false) === true) {
            $key = $this->resolver->string('mifare.key');
            $sector = $this->resolver->string('mifare.sector');

            if ($key && str_starts_with($key, 'ENV_')) {
                $key = env(substr($key, 4));
            }

            if ($sector && str_starts_with($sector, 'ENV_')) {
                $sector = env(substr($sector, 4));
            }

            $this->mifare = $key && $sector;

            $this->mifareKey = $key;
            $this->mifareSector = intval($sector);
        } else {
            $this->mifare = false;

            $this->mifareKey = null;
            $this->mifareSector = null;
        }

        $this->setLogger(file_logger($this->resolver->string('log', 'intercom')));
    }

    public function open(int $value): void
    {
    }

    public function call(int $apartment): void
    {
    }

    public function callStop(): void
    {
    }

    public function reboot(): void
    {
    }

    public function reset(): void
    {
    }

    public static function template(string $value, array $values): string
    {
        if (preg_match_all('(%\w+%)', $value, $matches)) {
            foreach ($matches as $match) {
                foreach ($match as $item) {
                    $key = substr($item, 1, -1);

                    if (array_key_exists($key, $values)) {
                        $value = str_replace($item, $values[$key], $value);
                    }
                }
            }
        }

        return $value;
    }
}