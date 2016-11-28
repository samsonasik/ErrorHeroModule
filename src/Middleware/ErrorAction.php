<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;
use Zend\View\Renderer\PhpRenderer;

class ErrorAction
{
    use HeroTrait;

    /**
     * @param  array       $errorHeroModuleConfig
     * @param  Logging     $logging
     * @param  PhpRenderer $renderer
     */
    public function __construct(
        array       $errorHeroModuleConfig,
        Logging     $logging,
        PhpRenderer $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
        $this->renderer              = $renderer;
    }

    public function phpError()
    {
        if ($this->errorHeroModuleConfig['display-settings']['display_errors'] === 0) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 0);
        }

        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    public function __invoke($request, $response, $next = null)
    {
        $this->phpError();

        if ($next !== null) {
            return $next($request, $response, $error);
        }

        return $response;
    }
}
