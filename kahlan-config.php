<?php

use Laminas\ServiceManager\AbstractPluginManager;
use Kahlan\Filter\Filters;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Xdebug;

// autoload hack
class_alias(AbstractPluginManager::class, Zend\ServiceManager\AbstractPluginManager::class);

Filters::apply($this, 'coverage', function($next): void {
    if (! extension_loaded('xdebug')) {
        return;
    }

    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->commandLine()->get('coverage'),
        'driver'    => new Xdebug(),
        'path'      => $this->commandLine()->get('src'),
        'exclude'   => [
            // laminas-cli preview console command
            'src/Command/Preview/ErrorPreviewConsoleCommand.php',

            // laminas-mvc preview page
            'src/Controller/ErrorPreviewController.php',

            // mezzio preview page
            'src/Middleware/Routed/Preview/ErrorPreviewAction.php',

            // compatible code
            'src/Compat/Logger.php',
            'src/Compat/LoggerAbstractServiceFactory.php'
        ],
        'colors'    => ! $this->commandLine()->get('no-colors')
    ]);
    $reporters->add('coverage', $coverage);
});
