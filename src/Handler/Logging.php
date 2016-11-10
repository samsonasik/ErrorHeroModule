<?php

namespace ErrorHeroModule\Handler;

use ErrorHeroModule\Listener\Mvc;
use Error;
use Exception;
use ErrorException;
use Zend\Log\Logger;
use Zend\Log\Writer\Db;

class Logging
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $serverUrl;

    /**
     * @var string
     */
    private $requestUri;

    /**
     * @param Logger $logger
     * @param string $serverUrl
     * @param string $requestUri
     */
    public function __construct(Logger $logger, $serverUrl, $requestUri)
    {
        $this->logger     = $logger;
        $this->serverUrl  = $serverUrl;
        $this->requestUri = $requestUri;
    }

    /**
     * @param $e
     */
    public function handleException($e)
    {
        $priority = Logger::ERR;
        if ($e instanceof ErrorException && isset(Logger::$errorPriorityMap[$e->getSeverity()])) {
            $priority = Logger::$errorPriorityMap[$e->getSeverity()];
        }

        $exceptionClass = get_class($e);

        $errorFile = $e->getFile();
        $errorLine = $e->getLine();

        $trace = $e->getTraceAsString();
        $i = 1;
        do {
            $messages[] = $i++ . ": " . $e->getMessage();
        } while ($e = $e->getPrevious());
        $implodeMessages = implode("\r\n", $messages);

        $extra = [
            'url'        => $this->serverUrl . $this->requestUri,
            'file'       => $errorFile,
            'line'       => $errorLine,
            'error_type' => $exceptionClass,
        ];
        $this->logger->log($priority, $implodeMessages, $extra);
    }

    /**
     * @param  int      $errorType
     * @param  string   $errorMessage
     * @param  string   $errorFile
     * @param  int      $errorLine
     * @param  string   $errorTypeString
     */
    public function handleError(
        $errorType,
        $errorMessage,
        $errorFile,
        $errorLine,
        $errorTypeString
    ) {
        $priority = Logger::$errorPriorityMap[$errorType];

        $extra = [
            'url'        => $this->serverUrl . $this->requestUri,
            'file'       => $errorFile,
            'line'       => $errorLine,
            'error_type' => $errorTypeString,
        ];
        $this->logger->log($priority, $errorMessage, $extra);
    }
}
