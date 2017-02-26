<?php

namespace ErrorHeroModule;

trait HeroTrait
{
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
        $errorTypeString = $this->errorType[$errorType];
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

        if ($this->errorHeroModuleConfig['display-settings']['display_errors'] === 0 || $errorExcluded) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 0);
        }

        if (! $errorExcluded) {
            $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
        }
    }
}
