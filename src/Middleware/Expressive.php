<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Seld\JsonLint\JsonParser;
use Throwable;
use Zend\Console\Console;
use Zend\Console\Response as ConsoleResponse;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Text\Table;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Expressive
{
    use HeroTrait;

    /**
     * @param array       $errorHeroModuleConfig
     * @param Logging     $logging
     * @param PhpRenderer $renderer
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (Throwable $t) {

        } catch (Exception $e) {
            
        }
    }
}
