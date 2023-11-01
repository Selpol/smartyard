<?php declare(strict_types=1);

namespace Selpol\Runner\Trait;

use Psr\Http\Message\ResponseInterface;
use Selpol\Device\Exception\DeviceException;
use Selpol\Framework\Entity\Exception\EntityException;
use Selpol\Framework\Http\Exception\HttpException;
use Selpol\Framework\Http\Response;
use Selpol\Service\Exception\AuthException;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

trait ResponseTrait
{
    function error(Throwable $throwable): int
    {
        try {
            if ($throwable instanceof HttpException)
                $response = $this->rbtResponse($throwable->getCode(), $throwable->getMessage());
            else if ($throwable instanceof ValidatorException)
                $response = $this->rbtResponse(400, $throwable->getValidatorMessage()->message);
            else if ($throwable instanceof DeviceException) {
                file_logger('device')->error($throwable);

                if ($throwable->getDevice()->asIp()?->ping())
                    $response = $this->rbtResponse(500, 'Ошибка взаимодействия с устройством');
                else
                    $response = $this->rbtResponse(500, 'Устройство не доступно');
            } else if ($throwable instanceof EntityException)
                $response = $this->rbtResponse($throwable->getCode(), $throwable->getLocalizedMessage());
            else if ($throwable instanceof AuthException)
                $response = $this->rbtResponse(401, $throwable->getMessage());
            else {
                file_logger('response')->error($throwable);

                $response = $this->rbtResponse(500);
            }

            return $this->emit($response);
        } catch (Throwable $throwable) {
            file_logger('response')->critical($throwable);

            return 1;
        }
    }

    protected function response(int $code = 200): Response
    {
        return http()->createResponse($code);
    }

    protected function rbtResponse(int $code = 200, ?string $message = null): Response
    {
        return json_response($code, body: [
            'code' => $code,
            'name' => Response::$codes[$code]['name'],
            'message' => $message ?: Response::$codes[$code]['message']
        ]);
    }

    protected function emit(ResponseInterface $response): int
    {
        try {
            header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

            foreach ($response->getHeaders() as $name => $values)
                header($name . ': ' . $response->getHeaderLine($name), false);

            if ($response->getStatusCode() != 204) {
                $body = $response->getBody();

                if ($body->getSize() > 1024 * 1024) {
                    $begin = 0;
                    $size = $body->getSize();
                    $end = $size - 1;

                    if (isset($_SERVER['HTTP_RANGE'])) {
                        if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                            $begin = intval($matches[1]);
                            if (!empty($matches[2]))
                                $end = intval($matches[2]);
                        }

                        header('HTTP/1.1 206 Partial Content');
                        header("Content-Range: bytes $begin-$end/$size");
                    } else
                        header('HTTP/1.1 200 OK');

                    $new_length = $end - $begin + 1;

                    header('Cache-Control: public, must-revalidate, max-age=0');
                    header('Pragma: no-cache');
                    header('Accept-Ranges: bytes');
                    header('Content-Length:' . $new_length);
                    header('Content-Transfer-Encoding: binary');

                    $chunk_size = 1024 * 1024;
                    $bytes_send = 0;

                    if (isset($_SERVER['HTTP_RANGE']))
                        $body->seek($begin);

                    while (!$body->eof() && !connection_aborted() && ($bytes_send < $new_length)) {
                        $buffer = $body->read($chunk_size);

                        echo $buffer;

                        $bytes_send += strlen($buffer);
                    }
                } else {
                    header('Content-Length: ' . $body->getSize());

                    echo $body->getContents();
                }

                $body->close();
            }
        } catch (Throwable $throwable) {
            file_logger('response')->emergency('Emergency error' . PHP_EOL . $throwable);
        }

        return 0;
    }
}