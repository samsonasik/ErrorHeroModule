<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler;

use ErrorException;
use ErrorHeroModule\HeroConstant;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;
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

    public function __construct(
        Logger             $logger,
        string             $serverUrl,
        RequestInterface   $request = null,
        string             $requestUri = '',
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
     */
    public function setServerRequestandRequestUri(ServerRequestInterface $request) : void
    {
        $this->request    = $request;
        $this->requestUri = \substr($request->getUri()->__toString(), \strlen($this->serverUrl));
    }

    /**
     * @throws RuntimeException when cannot connect to DB in the first place
     */
    private function isExists(string $errorFile, int $errorLine, string $errorMessage, string $url, string $errorType) : bool
    {
        $writers = $this->logger->getWriters()->toArray();
        foreach ($writers as $writer) {
            if ($writer instanceof Db) {
                try {
                    $handlerWriterDb = new Writer\Checker\Db($writer, $this->configLoggingSettings, $this->logWritersConfig);
                    if ($handlerWriterDb->isExists($errorFile, $errorLine, $errorMessage, $url, $errorType)) {
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

    private function getRequestData() : array
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

        if (! ($cookie_data = $request->getCookie())) {
            $cookie_data = [];
        }
        $cookie_data = (array) $cookie_data;

        return [
            'query'          => $query,
            'request_method' => $request_method,
            'body_data'      => $body_data,
            'raw_data'       => $raw_data,
            'files_data'     => $files_data,
            'cookie_data'    => $cookie_data,
        ];
    }

    private function collectErrorExceptionData(Throwable $t) : array
    {
        if ($t instanceof ErrorException && isset(Logger::$errorPriorityMap[$severity = $t->getSeverity()])) {
            $priority  = Logger::$errorPriorityMap[$severity];
            $errorType = HeroConstant::ERROR_TYPE[$severity];
        } else {
            $priority  = Logger::ERR;
            $errorType = \get_class($t);
        }

        $errorFile    = $t->getFile();
        $errorLine    = $t->getLine();
        $trace        = $t->getTraceAsString();
        $errorMessage = $t->getMessage();

        return [
            'priority'       => $priority,
            'errorType'      => $errorType,
            'errorFile'      => $errorFile,
            'errorLine'      => $errorLine,
            'trace'          => $trace,
            'errorMessage'   => $errorMessage,
        ];
    }

    private function collectErrorExceptionExtraData(array $collectedExceptionData) : array
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

    private function sendMail(int $priority, string $errorMessage, array $extra, string $subject) : void
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
                $extra['request_data']
            );
            $writer->setFormatter($formatter);

            (new Logger())->addWriter($writer)
                          ->log($priority, $errorMessage, $extra);
        }
    }

    public function handleErrorException(Throwable $t) : void
    {
        $collectedExceptionData = $this->collectErrorExceptionData($t);

        try {
            if (
                $this->isExists(
                    $collectedExceptionData['errorFile'],
                    $collectedExceptionData['errorLine'],
                    $collectedExceptionData['errorMessage'],
                    $this->serverUrl.$this->requestUri,
                    $collectedExceptionData['errorType']
                )
            ) {
                return;
            }

            $extra = $this->collectErrorExceptionExtraData($collectedExceptionData);
            $this->logger->log($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra);
        } catch (RuntimeException $e) {
            $collectedExceptionData = $this->collectErrorExceptionData($t);
            $extra                  = $this->collectErrorExceptionExtraData($collectedExceptionData);
        }

        $this->sendMail($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra, '['.$this->serverUrl.'] '.$collectedExceptionData['errorType'].' has thrown');
    }
}
