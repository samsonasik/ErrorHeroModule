<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler;

use ErrorException;
use ErrorHeroModule\HeroConstant;
use Laminas\Diactoros\Stream;
use Laminas\Http\Header\Cookie;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Stdlib\ParametersInterface;
use Laminas\Stdlib\RequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Webmozart\Assert\Assert;

use function basename;
use function get_current_user;
use function getcwd;
use function implode;
use function php_uname;
use function str_replace;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use const PHP_BINARY;
use const PHP_EOL;

final class Logging
{
    /**
     * @link http://tools.ietf.org/html/rfc3164
     *
     * @const int defined from the BSD Syslog message severities
     */
    public const EMERGENCY = 0;

    public const ALERT = 1;

    public const CRITICAL = 2;

    public const ERROR = 3;

    public const WARNING = 4;

    public const NOTICE = 5;

    public const INFO = 6;

    public const DEBUG = 7;

    /**
     * Map native PHP errors to priority
     *
     * @var array
     */
    public static $errorPriorityMap = [
        E_NOTICE            => self::NOTICE,
        E_USER_NOTICE       => self::NOTICE,
        E_WARNING           => self::WARNING,
        E_CORE_WARNING      => self::WARNING,
        E_USER_WARNING      => self::WARNING,
        E_ERROR             => self::ERROR,
        E_USER_ERROR        => self::ERROR,
        E_CORE_ERROR        => self::ERROR,
        E_RECOVERABLE_ERROR => self::ERROR,
        E_PARSE             => self::ERROR,
        E_COMPILE_ERROR     => self::ERROR,
        E_COMPILE_WARNING   => self::ERROR,
        // E_STRICT is deprecated in php 8.4
        2048              => self::DEBUG,
        E_DEPRECATED      => self::DEBUG,
        E_USER_DEPRECATED => self::DEBUG,
    ];

    /** @var string */
    private const PRIORITY = 'priority';

    /** @var string */
    private const ERROR_TYPE = 'errorType';

    /** @var string */
    private const ERROR_FILE = 'errorFile';

    /** @var string */
    private const ERROR_LINE = 'errorLine';

    /** @var string */
    private const TRACE = 'trace';

    /** @var string */
    private const ERROR_MESSAGE = 'errorMessage';

    /** @var string */
    private const SERVER_URL = 'server_url';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $includeFilesToAttachments = true
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestData(?RequestInterface $request): array
    {
        if (! $request instanceof HttpRequest) {
            return [];
        }

        Assert::isInstanceOf($request, HttpRequest::class);

        /** @var ParametersInterface $query */
        $query = $request->getQuery();
        /** @var ParametersInterface $post */
        $post = $request->getPost();
        /** @var ParametersInterface $files*/
        $files = $request->getFiles();

        $content = $request->getContent();

        if ($content instanceof Stream) {
            $content = (string) $content;
        }

        $queryData     = $query->toArray();
        $requestMethod = $request->getMethod();
        $bodyData      = $post->toArray();
        $rawData       = str_replace(PHP_EOL, '', $content);
        $filesData     = $this->includeFilesToAttachments
            ? $files->toArray()
            : [];

        $cookie     = $request->getCookie();
        $cookieData = $cookie instanceof Cookie
            ? $cookie->getArrayCopy()
            : [];
        $ipAddress  = (new RemoteAddress())->getIpAddress();

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
     * @return array{
     *      priority: int,
     *      errorType: string,
     *      errorFile: string,
     *      errorLine: int,
     *      trace: string,
     *      errorMessage: string
     *  }
     */
    private function collectErrorExceptionData(Throwable $throwable): array
    {
        if (
            $throwable instanceof ErrorException
            && isset(self::$errorPriorityMap[$severity = $throwable->getSeverity()])
        ) {
            $priority  = self::$errorPriorityMap[$severity];
            $errorType = HeroConstant::ERROR_TYPE[$severity];
        } else {
            $priority  = self::ERROR;
            $errorType = $throwable::class;
        }

        $errorFile     = $throwable->getFile();
        $errorLine     = $throwable->getLine();
        $traceAsString = $throwable->getTraceAsString();
        $errorMessage  = $throwable->getMessage();

        return [
            self::PRIORITY      => $priority,
            self::ERROR_TYPE    => $errorType,
            self::ERROR_FILE    => $errorFile,
            self::ERROR_LINE    => $errorLine,
            self::TRACE         => $traceAsString,
            self::ERROR_MESSAGE => $errorMessage,
        ];
    }

    /**
     * @return array{
     *      server_url: string,
     *      url: string,
     *      file: string,
     *      line: int,
     *      error_type: string,
     *      trace: string,
     *      request_data: array<string, mixed>
     * }
     */
    private function collectErrorExceptionExtraData(array $collectedExceptionData, ?RequestInterface $request): array
    {
        if (! $request instanceof HttpRequest) {
            $argv      = $_SERVER['argv'] ?? [];
            $serverUrl = php_uname('n');
            $url       = $serverUrl . ':' . basename((string) getcwd())
                . ' ' . get_current_user()
                . '$ ' . PHP_BINARY;

            $params = implode(' ', $argv);
            $url   .= $params;
        } else {
            $http      = $request->getUri();
            $serverUrl = $http->getScheme() . '://' . $http->getHost();
            $url       = $http->toString();
        }

        return [
            self::SERVER_URL => $serverUrl,
            'url'            => $url,
            'file'           => $collectedExceptionData[self::ERROR_FILE],
            'line'           => $collectedExceptionData[self::ERROR_LINE],
            'error_type'     => $collectedExceptionData[self::ERROR_TYPE],
            self::TRACE      => $collectedExceptionData[self::TRACE],
            'request_data'   => $this->getRequestData($request),
        ];
    }

    public function handleErrorException(Throwable $throwable, ?RequestInterface $request = null): void
    {
        $collectedExceptionData = $this->collectErrorExceptionData($throwable);
        /**
         * @var array{url: string, server_url: string, mixed} $extra
         */
        $extra = $this->collectErrorExceptionExtraData($collectedExceptionData, $request);

        try {
            unset($extra[self::SERVER_URL]);
            $this->logger->log(
                $collectedExceptionData[self::PRIORITY],
                $collectedExceptionData[self::ERROR_MESSAGE],
                $extra
            );
        } catch (RuntimeException $runtimeException) {
            $collectedExceptionData = $this->collectErrorExceptionData($runtimeException);
            $extra                  = $this->collectErrorExceptionExtraData($collectedExceptionData, $request);
            unset($extra[self::SERVER_URL]);
        }
    }
}
