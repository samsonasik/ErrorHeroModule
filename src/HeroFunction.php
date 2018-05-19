<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use Seld\JsonLint\JsonParser;
use Zend\Http\PhpEnvironment\Request;

function detectAjaxMessageContentType(string $message) : string
{
    return ((new JsonParser())->lint($message) === null)
        ? 'application/problem+json'
        : ((\strip_tags($message) === $message) ? 'text/plain' : 'text/html');
}

function getServerURLandRequestURI(Request $request) : array
{
    $uri       = $request->getUri();
    $serverUrl = $uri->getScheme() . '://' . $uri->getHost();
    $port      = $uri->getPort();
    if ($port !== 80) {
        $serverUrl .= ':' . $port;
    }
    $requestUri = $request->getRequestUri();

    return [
        'serverUrl'  => $serverUrl,
        'requestUri' => $requestUri,
    ];
}