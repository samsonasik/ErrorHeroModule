<?php

namespace ErrorHeroModule\Handler;

use Error;
use Exception;
use ErrorException;
use Zend\Log\Logger;

class Logging
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
            'url'  => 'url',
            'file' => $errorFile,
            'line' => $errorLine,
        ];
        $this->logger->log($priority, $implodeMessages, $extra);
    }

    /**
     * @param  int      $errorType
     * @param  string   $errorMessage
     * @param  string   $errorFile
     * @param  int      $errorLine
     */
    public function handleError(
        $errorType,
        $errorMessage,
        $errorFile,
        $errorLine
    ) {
        $priority = Logger::$errorPriorityMap[$errorType];

        $extra = [
            'url'  => 'url',
            'file' => $errorFile,
            'line' => $errorLine,
        ];
        $this->logger->log($priority, $errorMessage, $extra);
    }
}
