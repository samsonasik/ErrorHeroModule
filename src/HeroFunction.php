<?php

declare(strict_types=1);

namespace ErrorHeroModule;

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

    $isExcluded = false;
    foreach ($excludeExceptionsConfig as $excludeExceptionConfig) {
        if ($exceptionOrErrorClass === $excludeExceptionConfig) {
            $isExcluded = true;
            break;
        }

        if (
            is_array($excludeExceptionConfig)
            && $excludeExceptionConfig[0] === $exceptionOrErrorClass
            && $excludeExceptionConfig[1] === $throwable->getMessage()
        ) {
            $isExcluded = true;
            break;
        }
    }

    return $isExcluded;
}
