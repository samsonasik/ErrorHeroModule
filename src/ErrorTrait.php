<?php

namespace ErrorHeroModule;

use ErrorHeroModule\Handler\Logging;
use Zend\Console\Console;
use Zend\Text\Table;
use Zend\View\Model\ViewModel;

trait ErrorTrait
{
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
