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
     * @var array
     */
    private $configLoggingSettings;

    /**
     * @var array
     */
    private $logWritersConfig;

    /**
     * @param Logger $logger
     * @param string $serverUrl
     * @param string $requestUri
     * @param array $configLoggingSettings
     */
    public function __construct(
        Logger $logger,
        $serverUrl,
        $requestUri,
        array $configLoggingSettings,
        array $logWritersConfig
    ) {
        $this->logger                = $logger;
        $this->serverUrl             = $serverUrl;
        $this->requestUri            = $requestUri;
        $this->configLoggingSettings = $configLoggingSettings;
        $this->logWritersConfig      = $logWritersConfig;
    }

    /**
     * @param  string      $errorFile
     * @param  int         $errorLine
     * @param  string      $errorMessage
     * @param  string      $url
     * @return bool
     */
    private function isExists($errorFile, $errorLine, $errorMessage, $url)
    {
        $writers = $this->logger->getWriters()->toArray();
        foreach ($writers as $writer) {
            if ($writer instanceof Db) {
                $handlerWriterDb = new Writer\Db($writer, $this->configLoggingSettings, $this->logWritersConfig);
                if ($handlerWriterDb->isExists($errorFile, $errorLine, $errorMessage, $url)) {
                    return true;
                }
            }
        }

        return false;
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

        if ($this->isExists($errorFile, $errorLine, $implodeMessages, $this->serverUrl . $this->requestUri)) {
            return;
        }

        $extra = [
            'url'        => $this->serverUrl . $this->requestUri,
            'file'       => $errorFile,
            'line'       => $errorLine,
            'error_type' => $exceptionClass,
            'trace'      => $trace,
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
        if ($this->isExists($errorFile, $errorLine, $errorMessage, $this->serverUrl . $this->requestUri)) {
            return;
        }

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
