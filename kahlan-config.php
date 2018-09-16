<?php // kahlan-config.php

use App\Kernel;
use Kahlan\Filter\Filters;

Filters::apply($this, 'bootstrap', function($next) {

    require __DIR__ . '/vendor/autoload.php';

    $root = $this->suite()->root();
    $root->beforeAll(function () {
//        allow('spl_autoload_register')->toBeCalled()->andReturn(null);
    });

    return $next();

});