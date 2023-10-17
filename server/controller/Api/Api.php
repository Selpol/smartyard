<?php

namespace Selpol\Controller\Api;

use Selpol\Entity\Entity;
use Selpol\Http\Response;

class Api
{
    public static function GET(array $params): array|Response
    {
        return self::ANSWER(false, 'badRequest');
    }

    public static function POST(array $params): array|Response
    {
        return self::ANSWER(false, 'badRequest');
    }

    public static function PUT(array $params): array|Response
    {
        return self::ANSWER(false, 'badRequest');
    }

    public static function DELETE(array $params): array|Response
    {
        return self::ANSWER(false, 'badRequest');
    }

    /**
     * sends templated answer or error
     *
     * $result - sends error or success
     * if false sends error with error code $answer
     * if true sends json with parent $answer
     * with default params returns 204
     *
     * @param mixed $result
     * @param integer|boolean|array|string $answer
     * @param integer $cache
     * @return array
     */
    public static function ANSWER(mixed $result = true, int|bool|array|string $answer = false, int $cache = -1): array
    {
        if ($result === false)
            return self::ERROR($answer);
        else if (is_int($answer) || is_bool($answer) || is_string($answer))
            return self::SUCCESS($answer, $result, $cache);

        return self::ERROR('unknown');
    }

    /**
     * more specific (success only) return function
     *
     * @param string|bool|int $key
     * @param mixed $data
     * @param integer $cache
     *
     * @return array[]
     */
    public static function SUCCESS(string|bool|int $key, mixed $data, int $cache = -1): array
    {
        global $redis_cache_ttl;

        if ($data !== false)
            $r = ['200' => [$key => $data]];
        else
            $r = ['204' => false];

        if ($cache < 0)
            $cache = $redis_cache_ttl;

        $r[] = ['cache' => $cache];

        return $r;
    }

    /**
     * more specific (error only) return function
     *
     * @param string $error
     * @return array
     */
    public static function ERROR(string $error = ''): array
    {
        if (!$error) {
            $error = last_error();

            if (!$error)
                $error = 'unknown';
        }

        $errors = ['badRequest' => 400, 'forbidden' => 403, 'notFound' => 404, 'notAcceptable' => 406];

        $code = @$errors[$error] ?: 400;

        return [$code => ['error' => $error]];
    }

    /**
     * internal function for indexing methods
     *
     * @return boolean|string[]
     */
    public static function index(): array|bool
    {
        return false;
    }
}