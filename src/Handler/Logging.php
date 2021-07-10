<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler;

use ErrorException;
use ErrorHeroModule\Handler\Formatter\Json;
use ErrorHeroModule\Handler\Writer\Mail;
use ErrorHeroModule\HeroConstant;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Http\Header\Cookie;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Db;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Stdlib\RequestInterface;
use RuntimeException;
use Throwable;
use Webmozart\Assert\Assert;

use function basename;
use function get_current_user;
use function getcwd;
use function php_uname;
use function str_replace;

use const PHP_BINARY;
use const PHP_EOL;

class Logging
{
    private array $configLoggingSettings = [];
    private array $emailReceivers        = [];
    private string $emailSender;

    public function __construct(
        private Logger $logger,
        array $errorHeroModuleLocalConfig,
        private array $logWritersConfig,
        private ?Message $message = null,
        private ?TransportInterface $mailMessageTransport = null,
        private bool $includeFilesToAttachments = true
    ) {
        $this->configLoggingSettings = $errorHeroModuleLocalConfig['logging-settings'];
        $this->emailReceivers        = $errorHeroModuleLocalConfig['email-notification-settings']['email-to-send'];
        $this->emailSender           = $errorHeroModuleLocalConfig['email-notification-settings']['email-from'];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestData(RequestInterface $request): array
    {
        if ($request instanceof ConsoleRequest) {
            return [];
        }

        Assert::isInstanceOf($request, HttpRequest::class);
        $queryData     = $request->getQuery()->toArray();
        $requestMethod = $request->getMethod();
        $bodyData      = $request->getPost()->toArray();
        $rawData       = str_replace(PHP_EOL, '', (string) $request->getContent());
        $filesData     = $this->includeFilesToAttachments
            ? $request->getFiles()->toArray()
            : [];
        $cookie        = $request->getCookie();
        $cookieData    = $cookie instanceof Cookie
            ? $cookie->getArrayCopy()
            : [];
        $ipAddress     = (new RemoteAddress())->getIpAddress();

        return [
            'request_method' => $requestMethod,
            'query_data'     => $queryData,
            'body_data'      => $bodyData,
            'raw_data'       => $rawData,
            'files_data'     => $filesData,
            'cookie_data'    => $cookieData,
            'ip_address'     => $ipAddress,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectErrorExceptionData(Throwable $throwable): array
    {
        if (
            $throwable instanceof ErrorException
            && isset(Logger::$errorPriorityMap[$severity = $throwable->getSeverity()])
        ) {
            $priority  = Logger::$errorPriorityMap[$severity];
            $errorType = HeroConstant::ERROR_TYPE[$severity];
        } else {
            $priority  = Logger::ERR;
            $errorType = $throwable::class;
        }

        $errorFile     = $throwable->getFile();
        $errorLine     = $throwable->getLine();
        $traceAsString = $throwable->getTraceAsString();
        $errorMessage  = $throwable->getMessage();

        return [
            'priority'     => $priority,
            'errorType'    => $errorType,
            'errorFile'    => $errorFile,
            'errorLine'    => $errorLine,
            'trace'        => $traceAsString,
            'errorMessage' => $errorMessage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectErrorExceptionExtraData(array $collectedExceptionData, RequestInterface $request): array
    {
        if ($request instanceof ConsoleRequest) {
            $serverUrl = php_uname('n');
            $url       = $serverUrl . ':' . basename((string) getcwd())
                . ' ' . get_current_user()
                . '$ ' . PHP_BINARY . ' ' . $request->getScriptName();

            $params = $request->getParams()->toArray();
            unset($params['controller'], $params['action']);
            $request->getParams()->fromArray($params);
            $url .= ' ' . $request->toString();
        } else {
            Assert::isInstanceOf($request, HttpRequest::class);
            $uri       = $request->getUri();
            $serverUrl = $uri->getScheme() . '://' . $uri->getHost();
            $url       = $uri->toString();
        }

        return [
            'server_url'   => $serverUrl,
            'url'          => $url,
            'file'         => $collectedExceptionData['errorFile'],
            'line'         => $collectedExceptionData['errorLine'],
            'error_type'   => $collectedExceptionData['errorType'],
            'trace'        => $collectedExceptionData['trace'],
            'request_data' => $this->getRequestData($request),
        ];
    }

    /**
     * @throws RuntimeException When cannot connect to DB in the first place.
     */
    private function isExists(
        string $errorFile,
        int $errorLine,
        string $errorMessage,
        string $url,
        string $errorType
    ): bool {
        $writers = $this->logger->getWriters()->toArray();
        foreach ($writers as $writer) {
            if ($writer instanceof Db) {
                try {
                    $handlerWriterDb = new Writer\Checker\Db(
                        $writer,
                        $this->configLoggingSettings,
                        $this->logWritersConfig
                    );
                    if ($handlerWriterDb->isExists($errorFile, $errorLine, $errorMessage, $url, $errorType)) {
                        return true;
                    }
                    break;
                } catch (RuntimeException $runtimeException) {
                    // use \Laminas\Db\Adapter\Exception\RuntimeException but do here
                    // to avoid too much deep trace from Laminas\Db classes
                    throw new ${! ${''} = $runtimeException::class}($runtimeException->getMessage());
                }
            }
        }

        return false;
    }

    private function sendMail(int $priority, string $errorMessage, array $extra, string $subject): void
    {
        if (! $this->message || ! $this->mailMessageTransport) {
            return;
        }

        if (! $this->emailReceivers) {
            return;
        }

        $this->message->setFrom($this->emailSender);
        $this->message->setSubject($subject);

        $filesData = $extra['request_data']['files_data'] ?? [];
        foreach ($this->emailReceivers as $emailReceiver) {
            $this->message->setTo($emailReceiver);
            $writer = new Mail(
                $this->message,
                $this->mailMessageTransport,
                $filesData
            );
            $writer->setFormatter(new Json());

            (new Logger())->addWriter($writer)
                          ->log($priority, $errorMessage, $extra);
        }
    }

    public function handleErrorException(Throwable $throwable, RequestInterface $request): void
    {
        $collectedExceptionData = $this->collectErrorExceptionData($throwable);
        $extra                  = $this->collectErrorExceptionExtraData($collectedExceptionData, $request);
        $serverUrl              = $extra['server_url'];

        try {
            if (
                $this->isExists(
                    $collectedExceptionData['errorFile'],
                    $collectedExceptionData['errorLine'],
                    $collectedExceptionData['errorMessage'],
                    $extra['url'],
                    $collectedExceptionData['errorType']
                )
            ) {
                return;
            }

            unset($extra['server_url']);
            $this->logger->log($collectedExceptionData['priority'], $collectedExceptionData['errorMessage'], $extra);
        } catch (RuntimeException $e) {
            $collectedExceptionData = $this->collectErrorExceptionData($e);
            $extra                  = $this->collectErrorExceptionExtraData($collectedExceptionData, $request);
            unset($extra['server_url']);
        }

        $this->sendMail(
            $collectedExceptionData['priority'],
            $collectedExceptionData['errorMessage'],
            $extra,
            '[' . $serverUrl . '] ' . $collectedExceptionData['errorType'] . ' has thrown'
        );
    }
}
