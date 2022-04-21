<?php

use Kahlan\Filter\Filters;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Xdebug;

// autoload hack
file_put_contents('vendor/laminas/laminas-zendframework-bridge/src/autoload.php', '');
class_alias(Laminas\ServiceManager\AbstractPluginManager::class, Zend\ServiceManager\AbstractPluginManager::class);

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