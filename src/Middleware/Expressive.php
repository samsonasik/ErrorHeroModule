<?php

namespace ErrorHeroModule\Middleware;

use Error;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Zend\Console\Response as ConsoleResponse;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
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

        $this->phpError();

        try {
            return $next($request, $response);
        } catch (Error $t) {
            $this->exceptionError($t);
        } catch (Exception $e) {
            $this->exceptionError($e);
        }
    }

    /**
     *
     * @return void
     */
    public function phpError()
    {
        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    /**
     * @param  Error|Exception $e
     *
     * @return void
     */
    public function exceptionError($e)
    {
        $this->logging->handleException(
            $e
        );

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    /**
     * It show default view if display_errors setting = 0.
     *
     * @return void
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled()
    {
        $displayErrors = $this->errorHeroModuleConfig['display-settings']['display_errors'];

        if ($displayErrors === 0) {
            if (!Console::isConsole()) {

                $response = new Response();
                $response = $response->withStatus(500);

                $request          = new ServerRequest();
                $isXmlHttpRequest = $request->hasHeader('X_REQUESTED_WITH');

                if ($isXmlHttpRequest === true &&
                    isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
                ) {
                    $content     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
                    $contentType = ((new JsonParser())->lint($content) === null) ? 'application/problem+json' : 'text/html';

                    $response = $response->withHeader('Content-type', $contentType);
                    $response->setContent($content);

                    $response->send();
                    exit(-1);
                }
/*
                $view = new ViewModel();
                $view->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['view']);

                $layout = new ViewModel();
                $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);
                $layout->setVariable('content', $this->renderer->render($view));

                $response =  new HtmlResponse($this->renderer->render('error-her-module::home-page', $data));
                $response = $response->withHeader('Content-type', 'text/html');*/

                exit(-1);

            }

            $response = new ConsoleResponse();
            $response->setErrorLevel(-1);

            $table = new Table\Table([
                'columnWidths' => [150],
            ]);
            $table->setDecorator('ascii');
            $table->appendRow([$this->errorHeroModuleConfig['display-settings']['console']['message']]);

            $response->setContent($table->render());
            $response->send();

        }
    }
}
