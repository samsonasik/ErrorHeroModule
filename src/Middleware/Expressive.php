<?php

namespace ErrorHeroModule\Middleware;

use Error;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use Seld\JsonLint\JsonParser;
use Throwable;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Application;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\View\Model\ViewModel;

class Expressive implements MiddlewareInterface
{
    use HeroTrait;

    /**
     * @var ServerRequestInterface
     */
    private $request;

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
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return $handler->handle($request);
        }

        try {
            $this->request = $request;
            $this->logging->setServerRequestandRequestUri($request);

            $this->phpError();

            return $handler->handle($request);
        } catch (Throwable $t) {}

        return $this->exceptionError($t, $request);
    }

    /**
     *
     * @return void
     */
    public function phpError()
    {
        \register_shutdown_function([$this, 'execOnShutdown']);
        \set_error_handler([$this, 'phpErrorHandler']);
    }

    /**
     * @param  Throwable $t
     * @throws Error      when 'display_errors' config is 1 and Error has thrown
     * @throws Exception  when 'display_errors' config is 1 and Exception has thrown
     *
     * @return ResponseInterface
     */
    public function exceptionError($t, $request)
    {
        $exceptionOrErrorClass = \get_class($t);
        if (isset($this->errorHeroModuleConfig['display-settings']['exclude-exceptions']) &&
            \in_array($exceptionOrErrorClass, $this->errorHeroModuleConfig['display-settings']['exclude-exceptions'])
        ) {
            throw $t;
        }

        $this->logging->handleErrorException(
            $t
        );

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            throw $t;
        }

        return $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    /**
     * It show default view if display_errors setting = 0.
     *
     * @return ResponseInterface
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled()
    {
        $isXmlHttpRequest = $this->request->hasHeader('X-Requested-With');

        if ($isXmlHttpRequest === true &&
            isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
        ) {
            $content     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
            $contentType = ((new JsonParser())->lint($content) === null) ? 'application/problem+json' : 'text/html';

            $response = new Response();
            $response->getBody()->write($content);
            $response = $response->withHeader('Content-type', $contentType);
            $response = $response->withStatus(500);

            return $response;
        }

        $layout = new ViewModel();
        $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);

        $r = new ReflectionProperty($this->renderer, 'layout');
        $r->setAccessible(true);
        $r->setValue($this->renderer, $layout);

        $response =  new HtmlResponse(
            $this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view']),
            500
        );
        return $response;
    }
}
