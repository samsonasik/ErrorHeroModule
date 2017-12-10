<?php

namespace ErrorHeroModule\Handler;

use Error;
use ErrorException;
use ErrorHeroModule\HeroConstant;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\Request as HttpRequest;
use Zend\Log\Logger;
use Zend\Log\Writer\Db;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Stdlib\RequestInterface;

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
     * @var RequestInterface|ServerRequestInterface|null
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
     * @var array
     */
    private $requestData = [];

    /**
     * @param Logger                    $logger
     * @param string                    $serverUrl
     * @param string                    $requestUri
     * @param RequestInterface|null     $request
     * @param array                     $errorHeroModuleLocalConfig
     * @param array                     $logWritersConfig
     * @param Message|null              $mailMessageService
     * @param TransportInterface|null   $mailMessageTransport
     */
    public function __construct(
        Logger             $logger,
        $serverUrl,
        RequestInterface   $request = null,
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
     * @param ServerRequestInterface $request
     */
    public function setServerRequestandRequestUri(ServerRequestInterface $request)
    {
        $this->request    = $request;
        $this->requestUri = substr($request->getUri(), strlen($this->serverUrl));
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
                    break;
                } catch (RuntimeException $e) {
                    // use \Zend\Db\Adapter\Exception\RuntimeException but do here
                    // to avoid too much deep trace from Zend\Db classes
                    $exceptionClass = \get_class($e);
                    throw new $exceptionClass($e->getMessage());
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
        if (! $this->request || $this->request instanceof ConsoleRequest) {
            return [];
        }

        if ($this->request instanceof ServerRequestInterface) {
            $this->request = Psr7ServerRequest::toZend($this->request);
        }

        /** @var HttpRequest $request */
        $request = $this->request;

        $query          = $request->getQuery()->toArray();
        $request_method = $request->getMethod();
        $body_data      = $request->getPost()->toArray();
        $raw_data       = $request->getContent();
        $raw_data       = \str_replace(\PHP_EOL, '', $raw_data);
        $files_data     = $request->getFiles()->toArray();
        $cookie_data    = (array) $request->getCookie();

        return [
            'query'          => $query,
            'request_method' => $request_method,
            'body_data'      => $body_data,
            'raw_data'       => $raw_data,
            'files_data'     => $files_data,
            'cookie_data'    => $cookie_data,
        ];
    }

    /**
     * @param  Error|Exception $e
     *
     * @return array
     */
    private function collectErrorExceptionData($e)
    {
        if ($e instanceof ErrorException && isset(Logger::$errorPriorityMap[$severity = $e->getSeverity()])) {
            $priority  = Logger::$errorPriorityMap[$severity];
            $errorType = HeroConstant::ERROR_TYPE[$severity];
        } else {
            $priority  = Logger::ERR;
            $errorType = \get_class($e);
        }

        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        $trace     = $e->getTraceAsString();
        $errorMessage = $e->getMessage();

        return [
            'priority'       => $priority,
            'errorType'      => $errorType,
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
    private function collectErrorExceptionExtraData(array $collectedExceptionData)
    {
        return [
            'url'          => $this->serverUrl.$this->requestUri,
            'file'         => $collectedExceptionData['errorFile'],
            'line'         => $collectedExceptionData['errorLine'],
            'error_type'   => $collectedExceptionData['errorType'],
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
        if (! $this->mailMessageService || ! $this->mailMessageTransport) {
            return;
        }

        if (! $this->emailReceivers) {
            return;
        }

        $this->mailMessageService->setFrom($this->emailSender);
        $this->mailMessageService->setSubject($subject);

        $formatter = new Formatter\Json();
        foreach ($this->emailReceivers as $key => $email) {
            $this->mailMessageService->setTo($email);
            $writer    = new Writer\Mail(
                $this->mailMessageService,
                $this->mailMessageTransport,
                $this->requestData
            );
            $writer->setFormatter($formatter);

            (new Logger())->addWriter($writer)
                          ->log($priority, $errorMessage, $extra);
        }
    }

    /**
     * @param Error|Exception $e
     *
     * @return void
     */
    public function handleErrorException($e)
    {
        $collectedExceptionData = $this->collectErrorExceptionData($e);

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

            $extra = $this->collectErrorExceptionExtraData($collectedExceptionData);
            $this->logger->log($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra);
        } catch (RuntimeException $e) {
            $collectedExceptionData = $this->collectErrorExceptionData($e);
            $extra                  = $this->collectErrorExceptionExtraData($collectedExceptionData);
        }

        $this->requestData = $extra['request_data'];
        $this->sendMail($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra, '['.$this->serverUrl.'] '.$collectedExceptionData['errorType'].' has thrown');
    }
}
