<?php

namespace ErrorHeroModule;

use ErrorException;
use ErrorHeroModule\Handler\Logging;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\View\Renderer\PhpRenderer;

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

    /**
     * @var PhpRenderer|TemplateRendererInterface
     */
    private $renderer;

    /** @var string */
    private $result = '';

    public function phpFatalErrorHandler($buffer)
    {
        $error = \error_get_last();
        if (! $error) {
            return $buffer;
        }

        if (0 === strpos($error['message'], 'Uncaught')) {
            return $buffer;
        }

        if ($this->result === '') {
            return $buffer;
        }

        return $this->result;
    }

    public function execOnShutdown()
    {
        $error = \error_get_last();
        if (! $error) {
            return;
        }

        if (0 === strpos($error['message'], 'Uncaught')) {
            return;
        }

        $errorException = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        if (property_exists($this, 'request')) {
            $result = $this->exceptionError($errorException, $this->request);
            $this->result = (string) $result->getBody();

            return;
        }

        if (property_exists($this, 'mvcEvent')) {
            ob_start();
            $this->mvcEvent->setParam('exception', $errorException);
            $this->exceptionError($this->mvcEvent);
            $this->result = ob_get_clean();

            return;
        }
    }

    /**
     * @throws ErrorException when php error happen and error type is not excluded in the config
     */
    public function phpErrorHandler($errorType, $errorMessage, $errorFile, $errorLine)
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
