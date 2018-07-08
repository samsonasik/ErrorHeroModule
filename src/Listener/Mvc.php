<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use Assert\Assertion;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Zend\Console\Response as ConsoleResponse;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface;
use Zend\Text\Table;
use Zend\View\Renderer\PhpRenderer;

use function ErrorHeroModule\detectMessageContentType;

class Mvc extends AbstractListenerAggregate
{
    use HeroTrait;

    /**
     * @var PhpRenderer
     */
    private $renderer;

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

    public function phpError(MvcEvent $e) : void
    {
        \register_shutdown_function([$this, 'execOnShutdown']);
        \set_error_handler([$this, 'phpErrorHandler']);
    }

    public function exceptionError(MvcEvent $e) : void
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
            $exception,
            $request = $e->getRequest()
        );

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            // rely on original mvc process
            return;
        }

        $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled($e, $request);
    }

    /**
     * It show default view if display_errors setting = 0.
     */
    private function showDefaultViewWhenDisplayErrorSetttingIsDisabled(MvcEvent $e, RequestInterface $request) : void
    {
        if ($request instanceof Request) {
            $response = $e->getResponse();
            Assertion::isInstanceOf($response, Response::class);
            $response->setStatusCode(500);

            $isXmlHttpRequest = $request->isXmlHttpRequest();
            if ($isXmlHttpRequest === true &&
                isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
            ) {
                $application    = $e->getApplication();
                $events         = $application->getEventManager();
                $serviceManager = $application->getServiceManager();
                $serviceManager->get('SendResponseListener')
                               ->detach($events);

                $message     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
                $contentType = detectMessageContentType($message);

                $response->getHeaders()->addHeaderLine('Content-type', $contentType);
                $response->setContent($message);
                $response->send();

                return;
            }

            $layout = $e->getViewModel();
            $layout->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);
            $layout->setVariable(
                'content',
                $this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view'])
            );

            $response->setContent($this->renderer->render($layout));
            $e->setResponse($response);
            $e->stopPropagation(true);

            return;
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
