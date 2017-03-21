<?php

namespace ErrorHeroModule\Middleware;

use Error;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Console\Response as ConsoleResponse;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Expressive\Application;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\View\Model\ViewModel;
use Seld\JsonLint\JsonParser;
use Zend\Console\Console;
use Zend\Text\Table;
use Zend\Diactoros\Response\HtmlResponse;

class Expressive
{
    use HeroTrait;

    /**
     * @param array                     $errorHeroModuleConfig
     * @param Logging                   $logging
     * @param TemplateRendererInterface $renderer
     */
    public function __construct(
        array            $errorHeroModuleConfig,
        Logging          $logging,
        TemplateRendererInterface $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
        $this->renderer              = $renderer;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return $next($request, $response);
        }

        try {
            $response =  $next($request, $response);
            $this->phpError($request);

            return $response;
        } catch (Exception $e) {
            $this->exceptionError($e, $request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    public function phpError(ServerRequestInterface $request)
    {
        $this->request = $request;

        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    /**
     * @param  Error|Exception $e
     *
     * @return void
     */
    public function exceptionError($e, $request)
    {
        $this->logging->setServerRequestandRequestUri($request);
        $this->logging->handleException(
            $e
        );

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled($request);
    }

    /**
     * It show default view if display_errors setting = 0.
     *
     * @return void
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled($request)
    {
        $displayErrors = $this->errorHeroModuleConfig['display-settings']['display_errors'];
        if ($displayErrors) {
            return;
        }

        $response = new Response();
        $response = $response->withStatus(500);

        $isXmlHttpRequest = $request->hasHeader('X_REQUESTED_WITH');

        if ($isXmlHttpRequest === true &&
            isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
        ) {
            $content     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
            $contentType = ((new JsonParser())->lint($content) === null) ? 'application/problem+json' : 'text/html';

            $response = $response->withHeader('Content-type', $contentType);
            $response->getBody()->write($content);

            echo $response->getBody()->__toString();

            exit(-1);
        }

        $response =  new HtmlResponse($this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view']));
        $response = $response->withHeader('Content-type', 'text/html');

        echo $response->getBody()->__toString();

        exit(-1);
    }
}
