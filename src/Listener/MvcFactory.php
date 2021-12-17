<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;

class MvcFactory
{
    public function __invoke(ContainerInterface $container): Mvc
    {
        /** @var array $config */
        $config = $container->get('config');
        /** @var Logging $logging */
        $logging = $container->get(Logging::class);
        /** @var PhpRenderer $viewRenderer */
        $viewRenderer = $container->get('ViewRenderer');

        return new Mvc(
            $config['error-hero-module'],
            $logging,
            $viewRenderer
        );
    }
}
