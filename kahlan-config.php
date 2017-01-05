<?php

use Kahlan\Filter\Filter;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Xdebug;

Filter::register('kahlan.coverage', function($chain) {

    if (!extension_loaded('xdebug')) {
        return;
    }

    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->commandLine()->get('coverage'),
        'driver'    => new Xdebug(),
        'colors'    => !$this->commandLine()->get('no-colors'),
        'path'      => 'src',
        'exclude'   => [
            'src/Controller/ErrorPreviewConsoleZF2Controller.php',
        ],
    ]);

    $reporters->add('coverage', $coverage);
});
Filter::apply($this, 'coverage', 'kahlan.coverage');
