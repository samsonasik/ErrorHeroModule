<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use function ErrorHeroModule\detectMessageContentType;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use function ErrorHeroModule\isExcludedException;
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

class Mvc extends AbstractListenerAggregate
{
    use HeroTrait;

    private ?MvcEvent $mvcEvent = null;

    public function __construct(
        private array $errorHeroModuleConfig,
        private Logging $logging,
        private PhpRenderer $phpRenderer
    ) {
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $eventManager, $priority = 1): void
    {
        if (! $this->errorHeroModuleConfig['enable']) {
            return;
        }

        // exceptions
        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'exceptionError']);
        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'exceptionError'], 100);

        // php errors
        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'phpError']);
    }

    public function exceptionError(MvcEvent $mvcEvent): void
    {
        $exception = $mvcEvent->getParam('exception');
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
            $request = $mvcEvent->getRequest()
        );

        if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
            // rely on original mvc process
            return;
        }

        // show default view if display_errors setting = 0.
        $this->showDefaultView($mvcEvent, $request);
    }

    private function showDefaultView(MvcEvent $mvcEvent, RequestInterface $request): void
    {
        if ($request instanceof Request) {
            $response = $mvcEvent->getResponse();
            Assert::isInstanceOf($response, Response::class);
            $response->setStatusCode(500);

            $application    = $mvcEvent->getApplication();
            $eventManager   = $application->getEventManager();
            $serviceLocator = $application->getServiceManager();
            $serviceLocator->get('SendResponseListener')
                           ->detach($eventManager);

            $isXmlHttpRequest = $request->isXmlHttpRequest();
            if (
                $isXmlHttpRequest &&
                isset($this->errorHeroModuleConfig['display-settings']['ajax']['message'])
            ) {
                $message     = $this->errorHeroModuleConfig['display-settings']['ajax']['message'];
                $contentType = detectMessageContentType($message);

                $response->getHeaders()->addHeaderLine('Content-type', $contentType);
                $response->setContent($message);
                $response->send();

                return;
            }

            $model = $mvcEvent->getViewModel();
            $model->setTemplate($this->errorHeroModuleConfig['display-settings']['template']['layout']);
            $model->setVariable(
                $model->captureTo(),
                $this->phpRenderer->render($this->errorHeroModuleConfig['display-settings']['template']['view'])
            );

            $response->setContent($this->phpRenderer->render($model));
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
