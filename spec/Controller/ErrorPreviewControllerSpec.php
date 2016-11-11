<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule\Controller\ErrorPreviewController;
use Zend\View\Model\ViewModel;

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
            expect($closure)->toThrow(new \Exception('a sample error preview'));

        });

    });

});
