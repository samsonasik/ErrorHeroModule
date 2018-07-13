<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Closure;
use Error;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\ZendView\ZendViewRenderer;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\View\Model\ViewModel;

use function ErrorHeroModule\detectMessageContentType;

class Expressive implements MiddlewareInterface
{
    use HeroTrait;

    /**
     * @var TemplateRendererInterface|null
     */
    private $renderer;

    public function __construct(
        array                     $errorHeroModuleConfig,
        Logging                   $logging,
        TemplateRendererInterface $renderer = null
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
        $this->renderer              = $renderer;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return $handler->handle($request);
        }

        try {
            $this->phpError();
            return $handler->handle($request);
        } catch (Throwable $t) {}

        return $this->exceptionError($t, $request);
    }

    public function phpError() : void
    {
        \register_shutdown_function([$this, 'execOnShutdown']);
        \set_error_handler([$this, 'phpErrorHandler']);
    }

    /**
     * @throws Error      when 'display_errors' config is 1 and Error has thrown
     * @throws Exception  when 'display_errors' config is 1 and Exception has thrown
     */
    public function exceptionError(Throwable $t, ServerRequestInterface $request) : ResponseInterface
    {
        $exceptionOrErrorClass = \get_class($t);
        if (isset($this->errorHeroModuleConfig['display-settings']['exclude-exceptions']) &&
            \in_array($exceptionOrErrorClass, $this->errorHeroModuleConfig['display-settings']['exclude-exceptions'])
        ) {
            throw $t;
        }

        $this->logging->handleErrorException(
            $t,
            Psr7ServerRequest::toZend($request)
        );

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            throw $t;
        }

        // show default view if display_errors setting = 0.
        return $this->showDefaultView($request);
    }

    private function responseByConfigMessage($key) : ResponseInterface
    {
        $message     = $this->errorHeroModuleConfig['display-settings'][$key]['message'];
        $contentType = detectMessageContentType($message);

        $response = new Response();
        $response->getBody()->write($message);
        $response = $response->withHeader('Content-type', $contentType);

        return $response->withStatus(500);
    }

    private function showDefaultView(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->renderer === null) {
            return $this->responseByConfigMessage('no_template');
        }

        $isXmlHttpRequest = $request->hasHeader('X-Requested-With');
        if ($isXmlHttpRequest === true &&
            isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
        ) {
            return $this->responseByConfigMessage('ajax');
        }

        if ($this->renderer instanceof ZendViewRenderer) {
            $layout = new ViewModel();
            $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);

            $rendererLayout = & Closure::bind(function & ($renderer) {
                return $renderer->layout;
            }, null, $this->renderer)($this->renderer);
            $rendererLayout = $layout;
        }

        return new HtmlResponse(
            $this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view']),
            500
        );
    }
}
