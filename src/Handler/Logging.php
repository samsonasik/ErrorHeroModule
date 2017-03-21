<?php

namespace ErrorHeroModule\Handler;

use Error;
use ErrorException;
use Exception;
use RuntimeException;
use Zend\Diactoros\ServerRequest;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Log\Logger;
use Zend\Log\Writer\Db;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\SplPriorityQueue;

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
     * @var RequestInterface|ServerRequest
     */
    private $request;

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
     * @var Message|null
     */
    private $mailMessageService;

    /**
     * @var TransportInterface|null
     */
    private $mailMessageTransport;

    /**
     * @var array
     */
    private $emailReceivers;

    /**
     * @var string
     */
    private $emailSender;

    /**
     * @param Logger                         $logger
     * @param string                         $serverUrl
     * @param string                         $requestUri
     * @param RequestInterface|ServerRequest $request
     * @param array                          $errorHeroModuleLocalConfig
     * @param array                          $logWritersConfig
     * @param Message|null                   $mailMessageService
     * @param TransportInterface|null        $mailMessageTransport
     */
    public function __construct(
        Logger             $logger,
        $serverUrl,
        $request = null,
        $requestUri = '',
        array              $errorHeroModuleLocalConfig,
        array              $logWritersConfig,
        Message            $mailMessageService = null,
        TransportInterface $mailMessageTransport = null
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
        $this->emailSender           = $errorHeroModuleLocalConfig['email-notification-settings']['email-from'];
    }

    /**
     * Set ServerRequest for expressive
     *
     * @param ServerRequest $request
     */
    public function setServerRequestandRequestUri(ServerRequest $request)
    {
        $this->request = $request;
        $this->requestUri = $request->getUri()->getPath();
    }

    /**
     * @param string $errorFile
     * @param int    $errorLine
     * @param string $errorMessage
     * @param string $url
     *
     * @throws RuntimeException when cannot connect to DB in the first place
     *
     * @return bool
     */
    private function isExists($errorFile, $errorLine, $errorMessage, $url)
    {
        $writers = $this->logger->getWriters()->toArray();
        foreach ($writers as $writer) {
            if ($writer instanceof Db) {
                try {
                    $handlerWriterDb = new Writer\Db($writer, $this->configLoggingSettings, $this->logWritersConfig);
                    if ($handlerWriterDb->isExists($errorFile, $errorLine, $errorMessage, $url)) {
                        return true;
                    }
                } catch (RuntimeException $e) {
                    //new RuntimeException instance is on purpose to avoid long trace from \Zend\Db\Adapter\Exception\RuntimeException
                    throw new RuntimeException($e->getMessage());
                }
            }
        }

        return false;
    }

    /**
     * Get Request Data.
     *
     * @return array
     */
    private function getRequestData()
    {
        $request_data = [];
        if ($this->request instanceof HttpRequest) {
            $query          = $this->request->getQuery()->toArray();
            $request_method = $this->request->getServer('REQUEST_METHOD');
            $body_data      = ($this->request->isPost())
                ? $this->request->getPost()->toArray()
                : [];
            $raw_data       = $this->request->getContent();
            $raw_data       = str_replace("\r\n", '', $raw_data);
            $files_data     = $this->request->getFiles()->toArray();

            $request_data = [
                'query'          => $query,
                'request_method' => $request_method,
                'body_data'      => $body_data,
                'raw_data'       => $raw_data,
                'files_data'     => $files_data,
            ];
        }

        if ($this->request instanceof ServerRequest) {
            $query          = $this->request->getQueryParams();
            $request_method = $this->request->getMethod();
            $body_data      = ($this->request->getMethod() === 'POST')
                ? (array) $this->request->getParsedBody()
                : [];
            $raw_data       = (array) $this->request->getBody();
            $raw_data       = str_replace("\r\n", '', $raw_data);
            $files_data     = $this->request->getUploadedFiles();

            $request_data = [
                'query'          => $query,
                'request_method' => $request_method,
                'body_data'      => $body_data,
                'raw_data'       => $raw_data,
                'files_data'     => $files_data,
            ];
        }

        return $request_data;
    }

    /**
     * @param  Error|Exception $e
     *
     * @return array
     */
    private function collectExceptionData($e)
    {
        $priority = Logger::ERR;
        if ($e instanceof ErrorException && isset(Logger::$errorPriorityMap[$e->getSeverity()])) {
            $priority = Logger::$errorPriorityMap[$e->getSeverity()];
        }

        $exceptionClass = get_class($e);

        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        $trace     = $e->getTraceAsString();

        $i = 1;
        do {
            $messages[] = $i++.': '.$e->getMessage();
        } while ($e = $e->getPrevious());
        $errorMessage = implode("\r\n", $messages);

        return [
            'priority'       => $priority,
            'exceptionClass' => $exceptionClass,
            'errorFile'      => $errorFile,
            'errorLine'      => $errorLine,
            'trace'          => $trace,
            'errorMessage'   => $errorMessage,
        ];
    }

    /**
     * @param  array                     $collectedExceptionData
     *
     * @return array
     */
    private function collectExceptionExtraData(array $collectedExceptionData)
    {
        return [
            'url'          => $this->serverUrl.$this->requestUri,
            'file'         => $collectedExceptionData['errorFile'],
            'line'         => $collectedExceptionData['errorLine'],
            'error_type'   => $collectedExceptionData['exceptionClass'],
            'trace'        => $collectedExceptionData['trace'],
            'request_data' => $this->getRequestData(),
        ];
    }

    /**
     * @param int    $priority
     * @param string $errorMessage
     * @param array  $extra
     * @param string $subject
     *
     * @return void
     */
    private function sendMail($priority, $errorMessage, $extra, $subject)
    {
        if ($this->mailMessageService !== null && $this->mailMessageTransport !== null) {
            foreach ($this->emailReceivers as $key => $email) {
                $logger = clone $this->logger;

                $this->mailMessageService->setFrom($this->emailSender);
                $this->mailMessageService->setTo($email);
                $this->mailMessageService->setSubject($subject);

                $writer    = new Writer\Mail(
                    $this->mailMessageService,
                    $this->mailMessageTransport,
                    $this->getRequestData()
                );
                $formatter = new Formatter\Json();
                $writer->setFormatter($formatter);

                // use setWriters() to clean up existing writers
                $splPriorityQueue = new SplPriorityQueue();
                $splPriorityQueue->insert($writer, 1);
                $logger->setWriters($splPriorityQueue);

                $logger->log($priority, $errorMessage, $extra);
            }
        }
    }

    /**
     * @param Error|Exception $e
     *
     * @return void
     */
    public function handleException($e)
    {
        $collectedExceptionData = $this->collectExceptionData($e);

        try {
            if (
                $this->isExists(
                    $collectedExceptionData['errorFile'],
                    $collectedExceptionData['errorLine'],
                    $collectedExceptionData['errorMessage'],
                    $this->serverUrl.$this->requestUri
                )
            ) {
                return;
            }

            $extra = $this->collectExceptionExtraData($collectedExceptionData);
            $this->logger->log($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra);
        } catch (RuntimeException $e) {
            $collectedExceptionData = $this->collectExceptionData($e);
            $extra                  = $this->collectExceptionExtraData($collectedExceptionData);
        }

        $this->sendMail($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra, '['.$this->serverUrl.'] '.$collectedExceptionData['exceptionClass'].' has thrown');
    }

    /**
     * @param int    $errorType
     * @param string $errorMessage
     * @param string $errorFile
     * @param int    $errorLine
     * @param string $errorTypeString
     *
     * @return void
     */
    public function handleError(
        $errorType,
        $errorMessage,
        $errorFile,
        $errorLine,
        $errorTypeString
    ) {
        if ($this->isExists($errorFile, $errorLine, $errorMessage, $this->serverUrl.$this->requestUri)) {
            return;
        }

        $priority = Logger::$errorPriorityMap[$errorType];

        $extra = [
            'url'          => $this->serverUrl.$this->requestUri,
            'file'         => $errorFile,
            'line'         => $errorLine,
            'error_type'   => $errorTypeString,
            'request_data' => $this->getRequestData(),
        ];
        $this->logger->log($priority, $errorMessage, $extra);
        $this->sendMail($priority, $errorMessage, $extra, '['.$this->serverUrl.'] '.$errorTypeString.' PHP Error');
    }
}
