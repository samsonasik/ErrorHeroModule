<?php

declare(strict_types=1);

namespace ErrorHeroModule\Compat;

use DateTime;
use ErrorException;
use Exception;
use Laminas\Log\Exception\InvalidArgumentException;
use Laminas\Log\Exception\RuntimeException;
use Laminas\Log\LoggerInterface;
use Laminas\Log\Processor\ProcessorInterface;
use Laminas\Log\ProcessorPluginManager;
use Laminas\Log\Writer\WriterInterface;
use Laminas\Log\WriterPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\SplPriorityQueue;
use Traversable;

use function array_reverse;
use function count;
use function error_get_last;
use function error_reporting;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;
use function property_exists;
use function register_shutdown_function;
use function restore_error_handler;
use function restore_exception_handler;
use function set_error_handler;
use function set_exception_handler;
use function sprintf;
use function var_export;

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

/**
 * Logging messages with a stack of backends
 *
 * @override \Laminas\Logger to support php 8.4
 */
class Logger implements LoggerInterface
{
    /**
     * @link http://tools.ietf.org/html/rfc3164
     *
     * @const int defined from the BSD Syslog message severities
     */
    public const EMERG = 0;

    public const ALERT = 1;

    public const CRIT = 2;

    public const ERR = 3;

    public const WARN = 4;

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
        E_WARNING           => self::WARN,
        E_CORE_WARNING      => self::WARN,
        E_USER_WARNING      => self::WARN,
        E_ERROR             => self::ERR,
        E_USER_ERROR        => self::ERR,
        E_CORE_ERROR        => self::ERR,
        E_RECOVERABLE_ERROR => self::ERR,
        E_PARSE             => self::ERR,
        E_COMPILE_ERROR     => self::ERR,
        E_COMPILE_WARNING   => self::ERR,
        // E_STRICT is deprecated in php 8.4
        2048              => self::DEBUG,
        E_DEPRECATED      => self::DEBUG,
        E_USER_DEPRECATED => self::DEBUG,
    ];

    /**
     * Registered error handler
     *
     * @var bool
     */
    protected static $registeredErrorHandler = false;

    /**
     * Registered shutdown error handler
     *
     * @var bool
     */
    protected static $registeredFatalErrorShutdownFunction = false;

    /**
     * Registered exception handler
     *
     * @var bool
     */
    protected static $registeredExceptionHandler = false;

    /**
     * List of priority code => priority (short) name
     *
     * @var array
     */
    protected $priorities = [
        self::EMERG  => 'EMERG',
        self::ALERT  => 'ALERT',
        self::CRIT   => 'CRIT',
        self::ERR    => 'ERR',
        self::WARN   => 'WARN',
        self::NOTICE => 'NOTICE',
        self::INFO   => 'INFO',
        self::DEBUG  => 'DEBUG',
    ];

    /**
     * Writers
     *
     * @var SplPriorityQueue
     */
    protected $writers;

    /**
     * Processors
     */
    protected SplPriorityQueue $processors;

    /**
     * Writer writerPlugins
     *
     * @var WriterPluginManager
     */
    protected $writerPlugins;

    /**
     * Processor writerPlugins
     *
     * @var ProcessorPluginManager
     */
    protected $processorPlugins;

    /**
     * Constructor
     *
     * Set options for a logger. Accepted options are:
     * - writers: array of writers to add to this logger
     * - exceptionhandler: if true register this logger as exceptionhandler
     * - errorhandler: if true register this logger as errorhandler
     *
     * @param  array|Traversable $options
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        $this->writers    = new SplPriorityQueue();
        $this->processors = new SplPriorityQueue();

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! $options) {
            return;
        }

        if (! is_array($options)) {
            throw new InvalidArgumentException(
                'Options must be an array or an object implementing \Traversable '
            );
        }

        // Inject writer plugin manager, if available
        if (
            isset($options['writer_plugin_manager'])
            && $options['writer_plugin_manager'] instanceof AbstractPluginManager
        ) {
            $this->setWriterPluginManager($options['writer_plugin_manager']);
        }

        // Inject processor plugin manager, if available
        if (
            isset($options['processor_plugin_manager'])
            && $options['processor_plugin_manager'] instanceof AbstractPluginManager
        ) {
            $this->setProcessorPluginManager($options['processor_plugin_manager']);
        }

        if (isset($options['writers']) && is_array($options['writers'])) {
            foreach ($options['writers'] as $writer) {
                if (! isset($writer['name'])) {
                    throw new InvalidArgumentException('Options must contain a name for the writer');
                }

                $priority      = $writer['priority'] ?? null;
                $writerOptions = $writer['options'] ?? null;

                $this->addWriter($writer['name'], $priority, $writerOptions);
            }
        }

        if (isset($options['processors']) && is_array($options['processors'])) {
            foreach ($options['processors'] as $processor) {
                if (! isset($processor['name'])) {
                    throw new InvalidArgumentException('Options must contain a name for the processor');
                }

                $priority         = $processor['priority'] ?? null;
                $processorOptions = $processor['options'] ?? null;

                $this->addProcessor($processor['name'], $priority, $processorOptions);
            }
        }

        if (isset($options['exceptionhandler']) && $options['exceptionhandler'] === true) {
            static::registerExceptionHandler($this);
        }

        if (isset($options['errorhandler']) && $options['errorhandler'] === true) {
            static::registerErrorHandler($this);
        }

        if (isset($options['fatal_error_shutdownfunction']) && $options['fatal_error_shutdownfunction'] === true) {
            static::registerFatalErrorShutdownFunction($this);
        }
    }

    /**
     * Shutdown all writers
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->writers as $writer) {
            try {
                $writer->shutdown();
            } catch (Exception) {
            }
        }
    }

    /**
     * Get writer plugin manager
     *
     * @return WriterPluginManager
     */
    public function getWriterPluginManager()
    {
        if (null === $this->writerPlugins) {
            $this->setWriterPluginManager(new WriterPluginManager(new ServiceManager()));
        }

        return $this->writerPlugins;
    }

    /**
     * Set writer plugin manager
     */
    public function setWriterPluginManager(WriterPluginManager $writerPluginManager): static
    {
        $this->writerPlugins = $writerPluginManager;
        return $this;
    }

    /**
     * Get writer instance
     *
     * @param string $name
     * @return WriterInterface
     */
    public function writerPlugin($name, ?array $options = null)
    {
        return $this->getWriterPluginManager()->get($name, $options);
    }

    /**
     * Add a writer to a logger
     *
     * @param  string|WriterInterface $writer
     * @param  int $priority
     * @throws InvalidArgumentException
     */
    public function addWriter($writer, $priority = 1, ?array $options = null): static
    {
        if (is_string($writer)) {
            $writer = $this->writerPlugin($writer, $options);
        } elseif (! $writer instanceof WriterInterface) {
            throw new InvalidArgumentException(sprintf(
                'Writer must implement %s\Writer\WriterInterface; received "%s"',
                __NAMESPACE__,
                get_debug_type($writer)
            ));
        }

        $this->writers->insert($writer, $priority);

        return $this;
    }

    /**
     * Get writers
     *
     * @return SplPriorityQueue
     */
    public function getWriters()
    {
        return $this->writers;
    }

    /**
     * Set the writers
     *
     * @throws InvalidArgumentException
     */
    public function setWriters(SplPriorityQueue $splPriorityQueue): static
    {
        foreach ($splPriorityQueue->toArray() as $writer) {
            if (! $writer instanceof WriterInterface) {
                throw new InvalidArgumentException(
                    'Writers must be a SplPriorityQueue of Laminas\Log\Writer'
                );
            }
        }

        $this->writers = $splPriorityQueue;
        return $this;
    }

    /**
     * Get processor plugin manager
     *
     * @return ProcessorPluginManager
     */
    public function getProcessorPluginManager()
    {
        if (null === $this->processorPlugins) {
            $this->setProcessorPluginManager(new ProcessorPluginManager(new ServiceManager()));
        }

        return $this->processorPlugins;
    }

    /**
     * Set processor plugin manager
     *
     * @param  string|ProcessorPluginManager $plugins
     * @throws InvalidArgumentException
     */
    public function setProcessorPluginManager($plugins): static
    {
        if (is_string($plugins)) {
            $plugins = new $plugins();
        }

        if (! $plugins instanceof ProcessorPluginManager) {
            throw new InvalidArgumentException(sprintf(
                'processor plugin manager must extend %s\ProcessorPluginManager; received %s',
                __NAMESPACE__,
                get_debug_type($plugins)
            ));
        }

        $this->processorPlugins = $plugins;
        return $this;
    }

    /**
     * Get processor instance
     *
     * @param string $name
     * @return ProcessorInterface
     */
    public function processorPlugin($name, ?array $options = null)
    {
        return $this->getProcessorPluginManager()->get($name, $options);
    }

    /**
     * Add a processor to a logger
     *
     * @param  string|ProcessorInterface $processor
     * @param  int $priority
     * @throws InvalidArgumentException
     */
    public function addProcessor($processor, $priority = 1, ?array $options = null): static
    {
        if (is_string($processor)) {
            $processor = $this->processorPlugin($processor, $options);
        } elseif (! $processor instanceof ProcessorInterface) {
            throw new InvalidArgumentException(sprintf(
                'Processor must implement Laminas\Log\ProcessorInterface; received "%s"',
                get_debug_type($processor)
            ));
        }

        $this->processors->insert($processor, $priority);

        return $this;
    }

    /**
     * Get processors
     */
    public function getProcessors(): SplPriorityQueue
    {
        return $this->processors;
    }

    /**
     * Add a message as a log entry
     *
     * @param  int $priority
     * @param  mixed $message
     * @param  array|Traversable $extra
     * @throws InvalidArgumentException If message can't be cast to string.
     * @throws InvalidArgumentException If extra can't be iterated over.
     * @throws RuntimeException If no log writer specified.
     */
    public function log($priority, $message, $extra = []): static
    {
        if (! is_int($priority) || ($priority < 0) || ($priority >= count($this->priorities))) {
            throw new InvalidArgumentException(sprintf(
                '$priority must be an integer >= 0 and < %d; received %s',
                count($this->priorities),
                var_export($priority, true)
            ));
        }

        if (is_object($message) && ! method_exists($message, '__toString')) {
            throw new InvalidArgumentException(
                '$message must implement magic __toString() method'
            );
        }

        if (! is_array($extra) && ! $extra instanceof Traversable) {
            throw new InvalidArgumentException(
                '$extra must be an array or implement Traversable'
            );
        } elseif ($extra instanceof Traversable) {
            $extra = ArrayUtils::iteratorToArray($extra);
        }

        if ($this->writers->count() === 0) {
            throw new RuntimeException('No log writer specified');
        }

        $timestamp = new DateTime();

        if (is_array($message)) {
            $message = var_export($message, true);
        }

        $event = [
            'timestamp'    => $timestamp,
            'priority'     => $priority,
            'priorityName' => $this->priorities[$priority],
            'message'      => (string) $message,
            'extra'        => $extra,
        ];

        /** @var ProcessorInterface $processor */
        foreach ($this->processors->toArray() as $processor) {
            $event = $processor->process($event);
        }

        /** @var WriterInterface $writer */
        foreach ($this->writers->toArray() as $writer) {
            $writer->write($event);
        }

        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function emerg($message, $extra = [])
    {
        return $this->log(self::EMERG, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function alert($message, $extra = [])
    {
        return $this->log(self::ALERT, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function crit($message, $extra = [])
    {
        return $this->log(self::CRIT, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function err($message, $extra = [])
    {
        return $this->log(self::ERR, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function warn($message, $extra = [])
    {
        return $this->log(self::WARN, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function notice($message, $extra = [])
    {
        return $this->log(self::NOTICE, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function info($message, $extra = [])
    {
        return $this->log(self::INFO, $message, $extra);
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return Logger
     */
    public function debug($message, $extra = [])
    {
        return $this->log(self::DEBUG, $message, $extra);
    }

    /**
     * Register logging system as an error handler to log PHP errors
     *
     * @link http://www.php.net/manual/function.set-error-handler.php
     *
     * @param  bool   $continueNativeHandler
     * @return mixed  Returns result of set_error_handler
     */
    public static function registerErrorHandler(Logger $logger, $continueNativeHandler = false): false|callable|null
    {
        // Only register once per instance
        if (static::$registeredErrorHandler) {
            return false;
        }

        $errorPriorityMap = static::$errorPriorityMap;

        $previous = set_error_handler(
            function ($level, $message, $file, $line) use ($logger, $errorPriorityMap, $continueNativeHandler): bool {
                $iniLevel = error_reporting();

                if (($iniLevel & $level) !== 0) {
                    $priority = $errorPriorityMap[$level] ?? Logger::INFO;

                    $logger->log($priority, $message, [
                        'errno' => $level,
                        'file'  => $file,
                        'line'  => $line,
                    ]);
                }

                return ! $continueNativeHandler;
            }
        );

        static::$registeredErrorHandler = true;
        return $previous;
    }

    /**
     * Unregister error handler
     */
    public static function unregisterErrorHandler(): void
    {
        restore_error_handler();
        static::$registeredErrorHandler = false;
    }

    /**
     * Register a shutdown handler to log fatal errors
     *
     * @link http://www.php.net/manual/function.register-shutdown-function.php
     */
    public static function registerFatalErrorShutdownFunction(Logger $logger): bool
    {
        // Only register once per instance
        if (static::$registeredFatalErrorShutdownFunction) {
            return false;
        }

        $errorPriorityMap = static::$errorPriorityMap;

        register_shutdown_function(function () use ($logger, $errorPriorityMap): void {
            $error = error_get_last();

            if (
                null === $error
                || ! in_array(
                    $error['type'],
                    [
                        E_ERROR,
                        E_PARSE,
                        E_CORE_ERROR,
                        E_CORE_WARNING,
                        E_COMPILE_ERROR,
                        E_COMPILE_WARNING,
                    ],
                    true
                )
            ) {
                return;
            }

            $logger->log(
                $errorPriorityMap[$error['type']],
                $error['message'],
                [
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]
            );
        });

        static::$registeredFatalErrorShutdownFunction = true;

        return true;
    }

    /**
     * Register logging system as an exception handler to log PHP exceptions
     *
     * @link http://www.php.net/manual/en/function.set-exception-handler.php
     */
    public static function registerExceptionHandler(Logger $logger): bool
    {
        // Only register once per instance
        if (static::$registeredExceptionHandler) {
            return false;
        }

        $errorPriorityMap = static::$errorPriorityMap;

        set_exception_handler(function ($exception) use ($logger, $errorPriorityMap): void {
            $logMessages = [];

            do {
                $priority = Logger::ERR;
                if ($exception instanceof ErrorException && isset($errorPriorityMap[$exception->getSeverity()])) {
                    $priority = $errorPriorityMap[$exception->getSeverity()];
                }

                $extra = [
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                ];
                if (property_exists($exception, 'xdebug_message') && $exception->xdebug_message !== null) {
                    $extra['xdebug'] = $exception->xdebug_message;
                }

                $logMessages[] = [
                    'priority' => $priority,
                    'message'  => $exception->getMessage(),
                    'extra'    => $extra,
                ];
                $exception     = $exception->getPrevious();
            } while ($exception);

            foreach (array_reverse($logMessages) as $logMessage) {
                $logger->log($logMessage['priority'], $logMessage['message'], $logMessage['extra']);
            }
        });

        static::$registeredExceptionHandler = true;
        return true;
    }

    /**
     * Unregister exception handler
     */
    public static function unregisterExceptionHandler(): void
    {
        restore_exception_handler();
        static::$registeredExceptionHandler = false;
    }
}
