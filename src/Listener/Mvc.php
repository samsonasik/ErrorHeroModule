<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Laminas\Console\Response as ConsoleResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface;
use Laminas\Text\Table;
use Laminas\View\Renderer\PhpRenderer;
use Webmozart\Assert\Assert;

use function ErrorHeroModule\detectMessageContentType;
use function ErrorHeroModule\isExcludedException;

class Mvc extends AbstractListenerAggregate
{
    use HeroTrait;

    /** @var MvcEvent */
    private $mvcEvent;

    public function __construct(
        array $errorHeroModuleConfig,
        Logging $logging,
        private PhpRenderer $renderer
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
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

    public function exceptionError(MvcEvent $e): void
    {
        $exception = $e->getParam('exception');
        if (! $exception) {
            return;
        }

        if (
            isset($this->errorHeroModuleConfig['display-settings']['exclude-exceptions'])
            && isExcludedException($this->errorHeroModuleConfig['display-settings']['exclude-exceptions'], $exception)
        ) {
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

    private function showDefaultView(MvcEvent $e, RequestInterface $request): void
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
            if (
                $isXmlHttpRequest === true &&
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
