<?php

namespace ErrorHeroModule;

use ErrorException;
use ErrorHeroModule\Handler\Logging;
use Zend\Diactoros\ServerRequest;
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

    /**
     * @var ServerRequest|null
     */
    private $request = null;

    /**
     * @return void
     */
    public function execOnShutdown()
    {
        $error = error_get_last();
        if ($error && $error['type']) {
            $this->phpErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * @param int    $errorType
     * @param string $errorMessage
     * @param string $errorFile
     * @param int    $errorLine
     *
     * @return void
     */
    public function phpErrorHandler($errorType, $errorMessage, $errorFile, $errorLine)
    {
        $errorTypeString = HeroConstant::ERROR_TYPE[$errorType];
        $errorExcluded = false;
        if ($errorLine) {
            if (in_array($errorType, $this->errorHeroModuleConfig['display-settings']['exclude-php-errors'])) {
                $errorExcluded = true;
            } else {
                $this->logging->handleError(
                    $errorType,
                    $errorMessage,
                    $errorFile,
                    $errorLine,
                    $errorTypeString
                );
            }
        }

        if (! $this->errorHeroModuleConfig['display-settings']['display_errors'] || $errorExcluded) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 0);
        }

        if (! $this->errorHeroModuleConfig['display-settings']['display_errors'] && ! $errorExcluded) {
            $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
        }

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            throw new ErrorException($errorMessage, 500, $errorType, $errorFile, $errorLine);
        }
    }
}
