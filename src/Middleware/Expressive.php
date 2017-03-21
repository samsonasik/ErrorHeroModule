<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Zend\Console\Response as ConsoleResponse;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\ZendView\ZendViewRenderer;
use Zend\View\Model\ViewModel;

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
        if (! $this->errorHeroModuleConfig['enable']) {
            return $next($request, $response);
        }

        try {
            return $next($request, $response);
        } catch (Throwable $t) {

        } catch (Exception $e) {

        }
    }

    /**
     * @param Event $e
     *
     * @return void
     */
    public function phpError()
    {
        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    /**
     * @param $e
     *
     * @return void
     */
    public function exceptionError($e)
    {
        $this->logging->handleException(
            $exception
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
                $response->setStatusCode(500);

                $request          = new ServerRequest();
                $isXmlHttpRequest = $request->isXmlHttpRequest();
                if ($isXmlHttpRequest === true &&
                    isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
                ) {
                    $content     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
                    $contentType = ((new JsonParser())->lint($content) === null) ? 'application/problem+json' : 'text/html';

                    $response->getHeaders()->addHeaderLine('Content-type', $contentType);
                    $response->setContent($content);

                    $response->send();
                    exit(-1);
                }

                $view = new ViewModel();
                $view->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['view']);

                $layout = new ViewModel();
                $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);
                $layout->setVariable('content', $this->renderer->render($view));

                $response->getHeaders()->addHeaderLine('Content-type', 'text/html');
                $response->setContent($this->renderer->render($layout));

                $response->send();
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
