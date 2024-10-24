<?php declare(strict_types=1);

namespace Selpol\Controller\Api\config;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Config\ConfigFeature;

readonly class intercom extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $id = rule()->id()->onItem('_id', $params);
        $optimize = rule()->bool()->onItem('optimize', $params);

        $intercom = intercom($id);

        if ($intercom instanceof IntercomDevice) {
            $values = ($optimize ? container(ConfigFeature::class)->getOptimizeConfigForIntercom($intercom->model, $intercom->intercom) : container(ConfigFeature::class)->getConfigForIntercom($intercom->model, $intercom->intercom))->getValues();

            return self::success(array_reduce(array_keys($values), static function (string $previous, string $key) use ($values): string {
                return $previous . $key . '=' . $values[$key] . PHP_EOL;
            }, ''));
        }

        return self::error('Не удалось найти параметры конфигурации');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Конфигурация] Получить конфигурацию домофона'];
    }
}