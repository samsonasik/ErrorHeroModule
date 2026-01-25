<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\SendResponseListener;
use Laminas\Stdlib\RequestInterface;
use Laminas\View\Renderer\PhpRenderer;
use Throwable;
use Webmozart\Assert\Assert;

use function ErrorHeroModule\detectMessageContentType;
use function ErrorHeroModule\isExcludedException;

final class Mvc extends AbstractListenerAggregate
{
    use HeroTrait;

    private ?MvcEvent $mvcEvent = null;

    private const string DISPLAY_SETTINGS = 'display-settings';

    private const string MESSAGE = 'message';

    public function __construct(
        private readonly array $errorHeroModuleConfig,
        private readonly Logging $logging,
        private readonly PhpRenderer $phpRenderer
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
        if (! $exception instanceof Throwable) {
            return;
        }

        if (
            isset($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['exclude-exceptions'])
            && isExcludedException(
                $this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['exclude-exceptions'],
                $exception
            )
        ) {
            // rely on original mvc process
            return;
        }

        $this->logging->handleErrorException(
            $exception,
            $request = $mvcEvent->getRequest()
        );

        if ($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['display_errors']) {
            // rely on original mvc process
            return;
        }

        // show default view if display_errors setting = 0.
        $this->showDefaultView($mvcEvent, $request);
    }

    private function showDefaultView(MvcEvent $mvcEvent, RequestInterface $request): void
    {
        Assert::isInstanceOf($request, Request::class);

        $response = $mvcEvent->getResponse();
        Assert::isInstanceOf($response, Response::class);
        $response->setStatusCode(500);

        $application    = $mvcEvent->getApplication();
        $eventManager   = $application->getEventManager();
        $serviceLocator = $application->getServiceManager();

        /** @var SendResponseListener $sendResponseListener */
        $sendResponseListener = $serviceLocator->get('SendResponseListener');
        $sendResponseListener->detach($eventManager);

        $isXmlHttpRequest = $request->isXmlHttpRequest();
        if (
            $isXmlHttpRequest &&
            isset($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['ajax'][self::MESSAGE])
        ) {
            $message     = $this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['ajax'][self::MESSAGE];
            $contentType = detectMessageContentType($message);

            $response->getHeaders()->addHeaderLine('Content-type', $contentType);
            $response->setContent($message);
            $response->send();

            return;
        }

        $model = $mvcEvent->getViewModel();
        $model->setTemplate($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['template']['layout']);
        $model->setVariable(
            $model->captureTo(),
            $this->phpRenderer->render($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['template']['view'])
        );

        $response->setContent($this->phpRenderer->render($model));
        $response->send();
    }
}
