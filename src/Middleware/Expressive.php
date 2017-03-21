<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Zend\Expressive\ZendView\ZendViewRenderer;

class Expressive
{
    use HeroTrait;

    /**
     * @param array            $errorHeroModuleConfig
     * @param Logging          $logging
     * @param ZendViewRenderer $renderer
     */
    public function __construct(
        array            $errorHeroModuleConfig,
        Logging          $logging,
        ZendViewRenderer $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
        $this->renderer              = $renderer;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (Throwable $t) {
            die;
        } catch (Exception $e) {
            die;
        }
    }
}
