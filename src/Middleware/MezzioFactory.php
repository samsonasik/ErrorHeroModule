<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use ArrayObject;
use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\Doctrine;
use ErrorHeroModule\Transformer\PimpleService;
use ErrorHeroModule\Transformer\SymfonyService;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Template\TemplateRendererInterface;
use Pimple\Psr11\Container as Psr11PimpleContainer;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function is_array;
use function sprintf;

final class MezzioFactory
{
    /** @var array<string, string> */
    private const array CONTAINERS_TRANSFORM = [
        ContainerBuilder::class     => SymfonyService::class,
        Psr11PimpleContainer::class => PimpleService::class,
    ];

    private function createMiddlewareInstance(ContainerInterface $container, array $configuration): Mezzio
    {
        /** @var Logging $logging */
        $logging = $container->get(Logging::class);
        /** @var ?TemplateRendererInterface $templateRenderer */
        $templateRenderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : null;

        return new Mezzio(
            $configuration['error-hero-module'],
            $logging,
            $templateRenderer
        );
    }

    /**
     * @return mixed[]
     */
    private function verifyConfig(iterable $configuration, string $containerClass): array
    {
        if (! is_array($configuration)) {
            Assert::isInstanceOf($configuration, ArrayObject::class);
            $configuration = $configuration->getArrayCopy();
        }

        if (! isset($configuration['db'])) {
            throw new RuntimeException(
                sprintf(
                    'db config is required for build "ErrorHeroModuleLogger" service by %s Container',
                    $containerClass
                )
            );
        }

        return $configuration;
    }

    public function __invoke(ContainerInterface $container): Mezzio
    {
        /** @var array<string, mixed> $configuration */
        $configuration = $container->get('config');

        if ($container->has(EntityManager::class) && ! isset($configuration['db'])) {
            return $this->createMiddlewareInstance(
                Doctrine::transform($container, $configuration),
                $configuration
            );
        }

        if ($container instanceof ServiceManager) {
            return $this->createMiddlewareInstance($container, $configuration);
        }

        $containerClass = $container::class;
        if (array_key_exists($containerClass, self::CONTAINERS_TRANSFORM)) {
            $configuration = $this->verifyConfig($configuration, $containerClass);
            $transformer   = self::CONTAINERS_TRANSFORM[$containerClass];

            return $this->createMiddlewareInstance(
                $transformer::transform($container, $configuration),
                $configuration
            );
        }

        throw new RuntimeException(sprintf(
            'container "%s" is unsupported',
            $containerClass
        ));
    }
}
