<?php

use Kahlan\Filter\Filters;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Xdebug;

// autoload hack
file_put_contents('vendor/laminas/laminas-zendframework-bridge/src/autoload.php', '');
class_alias(Laminas\Cache\PatternPluginManager\PatternPluginManagerV2Polyfill::class, Zend\Cache\PatternPluginManager\PatternPluginManagerV2Polyfill::class);
class_alias(Laminas\ServiceManager\AbstractPluginManager::class, Zend\ServiceManager\AbstractPluginManager::class);
class_alias(Laminas\ModuleManager\Feature\ConfigProviderInterface::class, Zend\ModuleManager\Feature\ConfigProviderInterface::class);
class_alias(Laminas\ModuleManager\Feature\InitProviderInterface::class, Zend\ModuleManager\Feature\InitProviderInterface::class);
class_alias(Laminas\ModuleManager\ModuleManagerInterface::class, Zend\ModuleManager\ModuleManagerInterface::class);
class_alias(Laminas\ModuleManager\Feature\BootstrapListenerInterface::class, Zend\ModuleManager\Feature\BootstrapListenerInterface::class);
class_alias(Laminas\EventManager\EventInterface::class, Zend\EventManager\EventInterface::class);
class_alias(Laminas\ModuleManager\Feature\ControllerProviderInterface::class, Zend\ModuleManager\Feature\ControllerProviderInterface::class);
class_alias(Laminas\ModuleManager\Feature\DependencyIndicatorInterface::class, Zend\ModuleManager\Feature\DependencyIndicatorInterface::class);
class_alias(Laminas\ServiceManager\AbstractFactoryInterface::class, Zend\ServiceManager\AbstractFactoryInterface::class);
class_alias(Laminas\ServiceManager\ServiceLocatorInterface::class, Zend\ServiceManager\ServiceLocatorInterface::class);
class_alias(Laminas\ServiceManager\FactoryInterface::class, Zend\ServiceManager\FactoryInterface::class);
class_alias(Laminas\Db\Adapter\AdapterInterface::class, Zend\Db\Adapter\AdapterInterface::class);
class_alias(Laminas\Stdlib\AbstractOptions::class, Zend\Stdlib\AbstractOptions::class);

Filters::apply($this, 'coverage', function($next) {
    if (! extension_loaded('xdebug')) {
        return;
    }

    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->commandLine()->get('coverage'),
        'driver'    => new Xdebug(),
        'path'      => $this->commandLine()->get('src'),
        'exclude'   => [
            'src/Controller/ErrorPreviewConsoleController.php',
            'src/Controller/ErrorPreviewController.php',
            'src/Middleware/Routed/Preview/ErrorPreviewAction.php',
        ],
        'colors'    => ! $this->commandLine()->get('no-colors')
    ]);
    $reporters->add('coverage', $coverage);
});