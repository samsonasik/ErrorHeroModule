<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use Seld\JsonLint\JsonParser;

function detectMessageContentType(string $message) : string
{
    return (new JsonParser())->lint($message) === null
        ? 'application/problem+json'
        : ((\strip_tags($message) === $message) ? 'text/plain' : 'text/html');
}