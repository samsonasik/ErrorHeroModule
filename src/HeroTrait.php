<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use ErrorException;
use ErrorHeroModule\Listener\Mvc;
use Laminas\Mvc\MvcEvent;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;

use function error_get_last;
use function error_reporting;
use function ini_set;
use function is_array;
use function ob_end_flush;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function register_shutdown_function;
use function set_error_handler;
use function str_starts_with;

use const E_ALL;
use const E_STRICT;

trait HeroTrait
{
    private string $result = '';

    public function phpError(mixed ...$args): void
    {
        if ($this instanceof Mvc) {
            Assert::count($args, 1);
            Assert::isInstanceOf($args[0], MvcEvent::class);

            $this->mvcEvent = $args[0];
        }

        if (! $this->errorHeroModuleConfig['display-settings']['display_errors']) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', '0');
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start([$this, 'phpFatalErrorHandler']);
        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    private static function isUncaught(string $message): bool
    {
        return str_starts_with($message, 'Uncaught');
    }

    public function phpFatalErrorHandler(string $buffer): string
    {
        $error = error_get_last();
        if ($error === null) {
            return $buffer;
        }

        return self::isUncaught($error['message']) || $this->result === ''
            ? $buffer
            : $this->result;
    }

    public function execOnShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        if (self::isUncaught($error['message'])) {
            return;
        }

        $errorException = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);

        // laminas-cli
        if (! $this instanceof Mvc && ! isset($this->request)) {
            ob_start();
            $result       = $this->exceptionError($errorException);
            $this->result = (string) ob_get_clean();

            return;
        }

        // Laminas Mvc project
        if ($this instanceof Mvc) {
            Assert::isInstanceOf($this->mvcEvent, MvcEvent::class);

            ob_start();
            $this->mvcEvent->setParam('exception', $errorException);
            $this->exceptionError($this->mvcEvent);
            $this->result = (string) ob_get_clean();

            return;
        }

        // Mezzio project
        Assert::implementsInterface($this->request, ServerRequestInterface::class);

        $result       = $this->exceptionError($errorException);
        $this->result = (string) $result->getBody();
    }

    /**
     * @throws ErrorException When php error happen and error type is not excluded in the config.
     */
    public function phpErrorHandler(int $errorType, string $errorMessage, string $errorFile, int $errorLine): void
    {
        if ((error_reporting() & $errorType) === 0) {
            return;
        }

        foreach ($this->errorHeroModuleConfig['display-settings']['exclude-php-errors'] as $excludePhpError) {
            if ($errorType === $excludePhpError) {
                return;
            }

            if (
                is_array($excludePhpError)
                && $excludePhpError[0] === $errorType
                && $excludePhpError[1] === $errorMessage
            ) {
                return;
            }
        }

        throw new ErrorException($errorMessage, 0, $errorType, $errorFile, $errorLine);
    }
}
