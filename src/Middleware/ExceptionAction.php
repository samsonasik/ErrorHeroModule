<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\HeroTrait;
use ErrorHeroModule\Handler\Logging;
use Exception;
use Zend\View\Renderer\PhpRenderer;


class ExceptionAction
{
    use HeroTrait;

    /**
     * @param  array       $errorHeroModuleConfig
     * @param  Logging     $logging
     * @param PhpRenderer $renderer
     */
    public function __construct(
        array                     $errorHeroModuleConfig,
        Logging                   $logging,
        PhpRenderer $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
        $this->renderer              = $renderer;
    }

    /**
     * @param Exception $exception
     */
    public function exceptionError(Exception $exception)
    {
        $this->logging->handleException(
            $exception
        );

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    public function __invoke($error, $request, $response, $next = null)
    {
        if ($error instanceof Exception) {
            $this->exceptionError($error);
        }

        if ($next !== null) {
            return $next($request, $response, $error);
        }

        return $response;
    }
}
