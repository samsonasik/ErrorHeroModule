<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\DoctrineToZendDb;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Zend\Db\Adapter\Adapter;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Log\Logger;
use Zend\Log\WriterPluginManager;
use Zend\ServiceManager\ServiceManager;

class ExpressiveFactory
{
    public function __invoke(ContainerInterface $container) : Expressive
    {
        $configuration = $container->get('config');

        if ($container->has(EntityManager::class) && ! isset($configuration['db'])) {
            $container = DoctrineToZendDb::transform($container, $configuration);
        }

        if ($container instanceof SymfonyContainerBuilder) {
            if (! isset($configuration['db'])) {
                throw new \RuntimeException('db config is required for build Zend\Db\Adapter\Adapter instance by Symfony Container');
            }

            $config = $configuration['db'];
            $serviceManager = new ServiceManager();
            if (isset($config['adapters'])) {
                foreach ($config['adapters'] as $key => $adapterConfig) {
                    $container->set($key, new Adapter($adapterConfig));
                    $serviceManager->setService($key, new Adapter($adapterConfig));
                }
            }
            $container->set(Adapter::class, new Adapter($config));
            $serviceManager->setService(Adapter::class, new Adapter($config));

            $writerPluginManager = new WriterPluginManager($serviceManager);
            $writers = $configuration['log']['ErrorHeroModuleLogger']['writers'];
            foreach ($writers as $key => $writer) {
                if ($writer['name'] === 'db') {
                    $writers[$key]['options']['db'] = $container->get($writers[$key]['options']['db']);
                    break;
                }
            }

            $logger = new Logger([
                'writer_plugin_manager' => $writerPluginManager,
                'writers'               => $writers
            ]);
            $container->set('ErrorHeroModuleLogger', $logger);
            unset($serviceManager);
        }

        return new Expressive(
            $configuration['error-hero-module'],
            $container->get(Logging::class),
            $container->get(TemplateRendererInterface::class)
        );
    }
}
