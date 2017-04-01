<?php

namespace ErrorHeroModule\Middleware;

use Error;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use Seld\JsonLint\JsonParser;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Application;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\View\Model\ViewModel;

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

    /**
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     * @param  callable               $next
     *
     * @return ResponseInterface|void
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return $next($request, $response);
        }

        try {
            $this->request = $request;
            $this->logging->setServerRequestandRequestUri($request);

            $this->phpError();

            return $next($request, $response);
        } catch (Error $e) {
        } catch (Exception $e) {
        }

        $this->exceptionError($e, $request);
    }

    /**
     *
     * @return void
     */
    public function phpError()
    {
        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            return;
        }

        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    /**
     * @param  Error|Exception $e
     * @throws Error      when 'display_errors' config is 1 and Error has thrown
     * @throws Exception  when 'display_errors' config is 1 and Exception has thrown
     *
     * @return void
     */
    public function exceptionError($e, $request)
    {
        $this->logging->handleException(
            $e
        );

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            throw $e;
        }

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    /**
     * It show default view if display_errors setting = 0.
     *
     * @return void
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled()
    {
        $response = new Response();
        $response = $response->withStatus(500);

        $isXmlHttpRequest = $this->request->hasHeader('X-Requested-With');

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

        $layout = new ViewModel();
        $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);

        $r = new ReflectionProperty($this->renderer, 'layout');
        $r->setAccessible(true);
        $r->setValue($this->renderer, $layout);

        $response =  new HtmlResponse($this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view']));
        $response = $response->withHeader('Content-type', 'text/html');

        echo $response->getBody()->__toString();

        exit(-1);
    }
}
