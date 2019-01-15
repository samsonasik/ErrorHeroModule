<?php

use Kahlan\Filter\Filters;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Phpdbg;
use Kahlan\Reporter\Coverage\Driver\Xdebug;

Filters::apply($this, 'coverage', function($next) {
    if (\PHP_SAPI === 'phpdbg') {
        $driver = new Phpdbg();
    }

    if (! isset($driver)) {
        if (! extension_loaded('xdebug')) {
            return;
        }
        $driver = new Xdebug();
    }

    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->commandLine()->get('coverage'),
        'driver'    => $driver,
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