<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use Assert\Assertion;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
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

class Mvc extends AbstractListenerAggregate
{
    use HeroTrait;

    public function __construct(
        array       $errorHeroModuleConfig,
        Logging     $logging,
        PhpRenderer $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
        $this->renderer              = $renderer;
    }

    public function attach(EventManagerInterface $events, $priority = 1) : void
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return;
        }

        // exceptions
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'exceptionError']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'exceptionError'], 100);

        // php errors
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'phpError']);
    }

    public function phpError(Event $e)
    {
        \register_shutdown_function([$this, 'execOnShutdown']);
        \set_error_handler([$this, 'phpErrorHandler']);
    }

    public function exceptionError(Event $e) : void
    {
        $exception = $e->getParam('exception');
        if (! $exception) {
            return;
        }

        $exceptionClass = \get_class($exception);
        if (isset($this->errorHeroModuleConfig['display-settings']['exclude-exceptions']) &&
            \in_array($exceptionClass, $this->errorHeroModuleConfig['display-settings']['exclude-exceptions'])) {
            // rely on original mvc process
            return;
        }

        $this->logging->handleErrorException(
            $exception
        );

        $displayErrors = $this->errorHeroModuleConfig['display-settings']['display_errors'];
        if ($displayErrors) {
            // rely on original mvc process
            return;
        }

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled();
    }

    /**
     * It show default view if display_errors setting = 0.
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled() : void
    {
        if (! Console::isConsole()) {
            $response = new HttpResponse();
            $response->setStatusCode(500);

            $request          = new Request();
            $isXmlHttpRequest = $request->isXmlHttpRequest();
            if ($isXmlHttpRequest === true &&
                isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
            ) {
                $message     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
                $contentType = $this->detectAjaxMessageContentType($message);

                $response->getHeaders()->addHeaderLine('Content-type', $contentType);
                $response->setContent($message);

                $response->send();
                exit(-1);
            }

            Assertion::isInstanceOf($this->renderer, PhpRenderer::class);

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
