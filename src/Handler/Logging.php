<?php

namespace ErrorHeroModule\Handler;

use ErrorHeroModule\Listener\Mvc;
use Error;
use Exception;
use ErrorException;
use Zend\Http\PhpEnvironment\Request;
use Zend\Json\Json;
use Zend\Log\Logger;
use Zend\Log\Writer\Db;
use Zend\Log\Writer\Mail;

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
     * @var \Zend\Mail\Message|null
     */
    private $mailMessageService;

    /**
     * @var \Zend\Mail\Transport\TransportInterface|null
     */
    private $mailMessageTransport;

    /**
     * @var array
     */
    private $emailReceivers;

    /**
     * @param Logger                                       $logger
     * @param string                                       $serverUrl
     * @param string                                       $requestUri
     * @param mixed                                        $request
     * @param array                                        $errorHeroModuleLocalConfig
     * @param array                                        $logWritersConfig
     * @param \Zend\Mail\Message|null                      $mailMessageService
     * @param \Zend\Mail\Transport\TransportInterface|null $mailMessageTransport
     */
    public function __construct(
        Logger $logger,
        $serverUrl,
        $request,
        $requestUri,
        array $errorHeroModuleLocalConfig,
        array $logWritersConfig,
        $mailMessageService = null,
        $mailMessageTransport = null
    ) {
        $this->logger                = $logger;
        $this->serverUrl             = $serverUrl;
        $this->request               = $request;
        $this->requestUri            = $requestUri;
        $this->configLoggingSettings = $errorHeroModuleLocalConfig['logging-settings'];
        $this->logWritersConfig      = $logWritersConfig;
        $this->mailMessageService    = $mailMessageService;
        $this->mailMessageTransport  = $mailMessageTransport;
        $this->emailReceivers        = $errorHeroModuleLocalConfig['email-notification-settings']['email-to-send'];
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
     * Get Request Data
     *
     * @return array
     */
    private function getRequestData()
    {
        $request_data = [];
        if ($this->request instanceof Request) {

            $query          = $this->request->getQuery()->toArray();
            $request_method = $this->request->getServer('REQUEST_METHOD');
            $body_data = ($this->request->isPost())
                ? $this->request->getPost()->toArray()
                : [];

            $raw_data   = $this->request->getContent();
            $raw_data   = str_replace("\r\n", "", $raw_data);
            $files_data = $this->request->getFiles()->toArray();

            $request_data = [
                'query'          => $query,
                'request_method' => $request_method,
                'body_data'      => $body_data,
                'raw_data'       => $raw_data,
                'files_data'     => $files_data
            ];
        }

        return Json::prettyPrint(Json::encode($request_data));
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
        $errorMessage = implode("\r\n", $messages);

        if ($this->isExists($errorFile, $errorLine, $errorMessage, $this->serverUrl . $this->requestUri)) {
            return;
        }

        $extra = [
            'url'        => $this->serverUrl . $this->requestUri,
            'file'       => $errorFile,
            'line'       => $errorLine,
            'error_type' => $exceptionClass,
            'trace'      => $trace,
            'request_data' => $this->getRequestData(),
        ];
        $this->logger->log($priority, $errorMessage, $extra);
        $this->sendMail($priority, $errorMessage, $extra);
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
            'request_data' => $this->getRequestData(),
        ];
        $this->logger->log($priority, $errorMessage, $extra);
        $this->sendMail($priority, $errorMessage, $extra);
    }

    private function sendMail($priority, $errorMessage, $extra)
    {
        if ($this->mailMessageService !== null && $this->mailMessageTransport !== null) {
            $writers = $this->logger->getWriters();
            $writers->next();

            foreach ($this->emailReceivers as $key => $email) {

                $this->mailMessageService->setTo($email);
                $writer = new Mail($this->mailMessageService, $this->mailMessageTransport);
                $this->logger->addWriter($writer);

                $this->logger->log($priority, $errorMessage, $extra);
                $writers->next();

            }
        }
    }
}
