<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use ErrorException;
use ErrorHeroModule\Handler\Logging;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;
use Zend\Mvc\MvcEvent;

trait HeroTrait
{
    /**
     * @var array
     */
    private $errorHeroModuleConfig;

    /**
     * @var Logging
     */
    private $logging;

    /** @var string */
    private $result = '';

    public function phpError() : void
    {
        if ($this instanceof Listener\Mvc) {
            Assert::count($args = \func_get_args(), 1);
            Assert::isInstanceOf($this->mvcEvent = $args[0], MvcEvent::class);
        }

        if (! $this->errorHeroModuleConfig['display-settings']['display_errors']) {
            \error_reporting(\E_ALL | \E_STRICT);
            \ini_set('display_errors', '0');
        }

        while (\ob_get_level() > 0) {
            \ob_end_flush();
        }

        \ob_start([$this, 'phpFatalErrorHandler']);
        \register_shutdown_function([$this, 'execOnShutdown']);
        \set_error_handler([$this, 'phpErrorHandler']);
    }

    private static function isUncaught(array $error) : bool
    {
        return 0 === strpos($error['message'], 'Uncaught');
    }

    public function phpFatalErrorHandler($buffer): string
    {
        $error = \error_get_last();
        if (! $error) {
            return $buffer;
        }

        return self::isUncaught($error) || $this->result === ''
            ? $buffer
            : $this->result;
    }

    public function execOnShutdown() : void
    {
        $error = \error_get_last();
        if (! $error) {
            return;
        }

        if (self::isUncaught($error)) {
            return;
        }

        $errorException = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);

        // ZF Mvc project
        if ($this instanceof Listener\Mvc) {
            Assert::isInstanceOf($this->mvcEvent, MvcEvent::class);

            ob_start();
            $this->mvcEvent->setParam('exception', $errorException);
            $this->exceptionError($this->mvcEvent);
            $this->result = (string) ob_get_clean();

            return;
        }

        // ZF Expressive project
        Assert::implementsInterface($this->request, ServerRequestInterface::class);

        $result       = $this->exceptionError($errorException);
        $this->result = (string) $result->getBody();
    }

    /**
     * @throws ErrorException when php error happen and error type is not excluded in the config
     */
    public function phpErrorHandler(int $errorType, string $errorMessage, string $errorFile, int $errorLine) : void
    {
        if (! (\error_reporting() & $errorType)) {
            return;
        }

        if (\in_array($errorType, $this->errorHeroModuleConfig['display-settings']['exclude-php-errors'])) {
            return;
        }

        throw new ErrorException($errorMessage, 0, $errorType, $errorFile, $errorLine);
    }
}
