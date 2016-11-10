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
    private $requestUri;

    /**
     * @param Logger $logger
     * @param string $requestUri
     */
    public function __construct(Logger $logger, $requestUri)
    {
        $this->logger     = $logger;
        $this->requestUri = $requestUri;
    }

    /**
     * @param $e
     */
    public function handleException($e)
    {
        $priority = Logger::$errorPriorityMap[Logger::ERR];
        if ($e instanceof ErrorException && isset(Logger::$errorPriorityMap[$e->getSeverity()])) {
            $priority = Logger::$errorPriorityMap[$e->getSeverity()];
        }

        $errorFile = $e->getFile();
        $errorLine = $e->getLine();

        $trace = $e->getTraceAsString();
        $i = 1;
        do {
            $messages[] = $i++ . ": " . $e->getMessage();
        } while ($e = $e->getPrevious());
        $implodeMessages = implode("\r\n", $messages);

        $extra = [
            'url'        => $this->requestUri,
            'file'       => $errorFile,
            'line'       => $errorLine,
            'error_type' => get_class($e),
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
            'url'        => $this->requestUri,
            'file'       => $errorFile,
            'line'       => $errorLine,
            'error_type' => $errorTypeString,
        ];
        $this->logger->log($priority, $errorMessage, $extra);
    }
}
