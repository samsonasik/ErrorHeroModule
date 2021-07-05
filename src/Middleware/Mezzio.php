<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Closure;
use Error;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Laminas\View\Model\ViewModel;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function ErrorHeroModule\detectMessageContentType;
use function ErrorHeroModule\isExcludedException;

class Mezzio implements MiddlewareInterface
{
    use HeroTrait;

    private ?ServerRequestInterface $request = null;

    /**
     * @param mixed[] $errorHeroModuleConfig
     */
    public function __construct(
        private array $errorHeroModuleConfig,
        private Logging $logging,
        private ?TemplateRendererInterface $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return $handler->handle($request);
        }

        try {
            $this->request = $request;
            $this->phpError();
            return $handler->handle($request);
        } catch (Throwable $throwable) {
        }

        return $this->exceptionError($throwable);
    }

    /**
     * @throws Error      When 'display_errors' config is 1 and Error has thrown.
     * @throws Exception  When 'display_errors' config is 1 and Exception has thrown.
     */
    public function exceptionError(Throwable $t): Response
    {
        if (
            isset($this->errorHeroModuleConfig['display-settings']['exclude-exceptions'])
            && isExcludedException($this->errorHeroModuleConfig['display-settings']['exclude-exceptions'], $t)
        ) {
            throw $t;
        }

        /** @var  ServerRequestInterface $request */
        $request = $this->request;
        $this->logging->handleErrorException(
            $t,
            Psr7ServerRequest::toLaminas($request)
        );

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            throw $t;
        }

        // show default view if display_errors setting = 0.
        return $this->showDefaultView();
    }

    private function showDefaultView(): Response|HtmlResponse
    {
        if ($this->renderer === null) {
            return $this->responseByConfigMessage('no_template');
        }

        /** @var  ServerRequestInterface $request */
        $request          = $this->request;
        $isXmlHttpRequest = $request->hasHeader('X-Requested-With')
            && $request->getHeaderLine('X-Requested-With') === 'XmlHttpRequest';

        if (
            $isXmlHttpRequest &&
            isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
        ) {
            return $this->responseByConfigMessage('ajax');
        }

        if ($this->renderer instanceof LaminasViewRenderer) {
            $layout = new ViewModel();
            $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);

            $rendererLayout = &Closure::bind(
                static fn&($renderer) => $renderer->layout,
                null,
                $this->renderer
            )($this->renderer);
            $rendererLayout = $layout;
        }

        return new HtmlResponse(
            $this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view']),
            500
        );
    }

    private function responseByConfigMessage(string $key): Response
    {
        $message     = $this->errorHeroModuleConfig['display-settings'][$key]['message'];
        $contentType = detectMessageContentType($message);

        $response = new Response();
        $response->getBody()->write($message);
        $response = $response->withHeader('Content-type', $contentType);

        return $response->withStatus(500);
    }
}
