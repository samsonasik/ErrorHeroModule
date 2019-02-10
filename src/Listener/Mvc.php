<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Webmozart\Assert\Assert;
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

    /**
     * @var MvcEvent
     */
    private $mvcEvent;

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
        $this->mvcEvent = $e;

        if (! $this->errorHeroModuleConfig['display-settings']['display_errors']) {
            \error_reporting(\E_ALL | \E_STRICT);
            \ini_set('display_errors', '0');
        }

        while (\ob_get_level() > 0) {
            \ob_end_flush();
        }

        \ob_start([$this, 'phpFatalErrorHandler']);
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

        // show default view if display_errors setting = 0.
        $this->showDefaultView($e, $request);
    }

    private function showDefaultView(MvcEvent $e, RequestInterface $request) : void
    {
        if ($request instanceof Request) {
            $response = $e->getResponse();
            Assert::isInstanceOf($response, Response::class);
            $response->setStatusCode(500);

            $application    = $e->getApplication();
            $events         = $application->getEventManager();
            $serviceManager = $application->getServiceManager();
            $serviceManager->get('SendResponseListener')
                           ->detach($events);

            $isXmlHttpRequest = $request->isXmlHttpRequest();
            if ($isXmlHttpRequest === true &&
                isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
            ) {
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
                $layout->captureTo(),
                $this->renderer->render($this->errorHeroModuleConfig['display-settings']['template']['view'])
            );

            $response->setContent($this->renderer->render($layout));
            $response->send();

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
