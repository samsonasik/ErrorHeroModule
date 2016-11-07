<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule\Module;

describe('Module', function () {
    beforeAll(function () {
        $this->module = new Module();
    });
 
    describe('->getConfig', function () {
        it('return "config" array', function () {
            $moduleConfig = include __DIR__ . '/../config/module.config.php';
            
            $actual = $this->module->getConfig();
            expect($actual)->toBe($moduleConfig);
        });
    });
});
