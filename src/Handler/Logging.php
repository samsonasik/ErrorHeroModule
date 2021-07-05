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
        private ?Message $mailMessageService = null,
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
        $query_data     = $request->getQuery()->toArray();
        $request_method = $request->getMethod();
        $body_data      = $request->getPost()->toArray();
        $raw_data       = str_replace(PHP_EOL, '', (string) $request->getContent());
        $files_data     = $this->includeFilesToAttachments
            ? $request->getFiles()->toArray()
            : [];
        $cookie         = $request->getCookie();
        $cookie_data    = $cookie instanceof Cookie
            ? $cookie->getArrayCopy()
            : [];
        $ip_address     = (new RemoteAddress())->getIpAddress();

        return [
            'request_method' => $request_method,
            'query_data'     => $query_data,
            'body_data'      => $body_data,
            'raw_data'       => $raw_data,
            'files_data'     => $files_data,
            'cookie_data'    => $cookie_data,
            'ip_address'     => $ip_address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectErrorExceptionData(Throwable $t): array
    {
        if ($t instanceof ErrorException && isset(Logger::$errorPriorityMap[$severity = $t->getSeverity()])) {
            $priority  = Logger::$errorPriorityMap[$severity];
            $errorType = HeroConstant::ERROR_TYPE[$severity];
        } else {
            $priority  = Logger::ERR;
            $errorType = $t::class;
        }

        $errorFile    = $t->getFile();
        $errorLine    = $t->getLine();
        $trace        = $t->getTraceAsString();
        $errorMessage = $t->getMessage();

        return [
            'priority'     => $priority,
            'errorType'    => $errorType,
            'errorFile'    => $errorFile,
            'errorLine'    => $errorLine,
            'trace'        => $trace,
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
        if (! $this->mailMessageService || ! $this->mailMessageTransport) {
            return;
        }

        if (! $this->emailReceivers) {
            return;
        }

        $this->mailMessageService->setFrom($this->emailSender);
        $this->mailMessageService->setSubject($subject);

        $filesData = $extra['request_data']['files_data'] ?? [];
        foreach ($this->emailReceivers as $email) {
            $this->mailMessageService->setTo($email);
            $writer = new Mail(
                $this->mailMessageService,
                $this->mailMessageTransport,
                $filesData
            );
            $writer->setFormatter(new Json());

            (new Logger())->addWriter($writer)
                          ->log($priority, $errorMessage, $extra);
        }
    }

    public function handleErrorException(Throwable $t, RequestInterface $request): void
    {
        $collectedExceptionData = $this->collectErrorExceptionData($t);
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
