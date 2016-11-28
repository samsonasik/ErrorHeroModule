<?php

namespace ErrorHeroModule;

use ErrorHeroModule\HeroTrait;
use ErrorHeroModule\Handler\Logging;
use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Text\Table;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;

trait HeroTrait
{
    public function exceptionError($e)
    {
        $exception = $e->getParam('exception');
        if (!$exception) {
            return;
        }

        $this->logging->handleException(
            $exception
        );

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    public function phpError($e)
    {
        if ($this->errorHeroModuleConfig['display-settings']['display_errors'] === 0) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 0);
        }

        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

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
     */
    public function phpErrorHandler($errorType, $errorMessage, $errorFile, $errorLine)
    {
        $errorTypeString = $this->errorType[$errorType];

        if ($errorLine &&
            !in_array(
                $errorType,
                $this->errorHeroModuleConfig['display-settings']['exclude-php-errors']
            )
        ) {
            $this->logging->handleError(
                $errorType,
                $errorMessage,
                $errorFile,
                $errorLine,
                $errorTypeString
            );
        }

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    /**
     * It show default view if display_errors setting = 0.
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled()
    {
        $displayErrors = $this->errorHeroModuleConfig['display-settings']['display_errors'];

        if ($displayErrors === 0) {
            if (!Console::isConsole()) {
                $view = new ViewModel();
                $view->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['view']);

                $layout = new ViewModel();
                $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);
                $layout->setVariable('content', $this->renderer->render($view));

                echo $this->renderer->render($layout);
            } else {
                $table = new Table\Table([
                    'columnWidths' => [150],
                ]);
                $table->setDecorator('ascii');
                $table->appendRow([$this->errorHeroModuleConfig['display-settings']['console']['message']]);

                echo $table->render();
            }

            exit(0);
        }
    }
}
