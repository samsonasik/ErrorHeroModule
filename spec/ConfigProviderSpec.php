<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule\ConfigProvider;

describe('ConfigProvider', function () {

    beforeAll(function () {
        $this->configProvider = new ConfigProvider();
    });

    describe('->__invoke()', function () {

        it('return "config" array with "dependencies" key', function () {

            $expected = [
                'dependencies' => [
                    'factories' => [
                        \ErrorHeroModule\Middleware\ErrorAction::class => \ErrorHeroModule\Middleware\ErrorActionFactory::class,
                        \ErrorHeroModule\Middleware\ExceptionAction::class => \ErrorHeroModule\Middleware\ExceptionActionFactory::class,
                    ],
                ],
            ];

            $actual = $this->configProvider->__invoke();
            expect($actual)->toBe($expected);

        });

    });

});
