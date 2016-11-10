<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule\ConfigProvider;

describe('ConfigProvider', function () {

    beforeAll(function () {
        $this->configProvider = new ConfigProvider();
    });

    describe('->__invoke()', function () {

        it('return "config" array with "dependencies" key', function () {

            $moduleConfig = include __DIR__.'/../config/module.config.php';
            $expected = [
                'dependencies' => $moduleConfig['service_manager'],
            ];

            $actual = $this->configProvider->__invoke();
            expect($actual)->toBe($expected);

        });

    });

});
