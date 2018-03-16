<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\PhpErrorException;

describe('ErrorPreviewController', function () {

    given('controller', function () {

        return new ErrorPreviewController();

    });

    describe('->exceptionAction()', function() {

        it('throw Exception', function() {

            $controller = $this->controller;
            $closure = function() use ($controller) {
                $controller->exceptionAction();
            };
            expect($closure)->toThrow(new \Exception('a sample exception preview'));

        });

    });

    describe('->noticeAction()', function() {

        it('Error', function() {

            $closure = function () {
                $this->controller->noticeAction();
            };

            $exception = new PhpErrorException('`E_NOTICE` Undefined offset: 1');
            expect($closure)->toThrow($exception);

        });

    });

});
