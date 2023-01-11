<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use ArrayLookup\AtLeast;
use Seld\JsonLint\JsonParser;
use Throwable;

use function is_array;
use function strip_tags;

function detectMessageContentType(string $message): string
{
    $jsonParser = new JsonParser();
    return $jsonParser->lint($message) === null
        ? 'application/problem+json'
        : (strip_tags($message) === $message ? 'text/plain' : 'text/html');
}

/**
 * @param array<int, string|array<int, string>> $excludeExceptionsConfig
 */
function isExcludedException(array $excludeExceptionsConfig, Throwable $throwable): bool
{
    $exceptionOrErrorClass = $throwable::class;
    $message               = $throwable->getMessage();

    /**
     * @param string|array<int, string> $excludeExceptionConfig
     */
    $filter = static function (mixed $excludeExceptionConfig) use ($exceptionOrErrorClass, $message): bool {
        if ($excludeExceptionConfig === $exceptionOrErrorClass) {
            return true;
        }

        return is_array($excludeExceptionConfig)
            && $excludeExceptionConfig[0] === $exceptionOrErrorClass
            && $excludeExceptionConfig[1] === $message;
    };

    return AtLeast::once($excludeExceptionsConfig, $filter);
}
