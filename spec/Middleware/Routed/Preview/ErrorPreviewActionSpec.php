<?php

namespace ErrorHeroModule\Spec\Middleware\Routed\Preview;

use ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

describe('ErrorPreviewAction', function () {

    given('middleware', function () {
        return new ErrorPreviewAction();
    });

    describe('->__invoke()', function () {

        it('throw Exception', function () {

            $closure = function () {
                $request  = Double::instance(['implements' => ServerRequestInterface::class]);
                allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('exception');

                $response = Double::instance(['implements' => ResponseInterface::class]);
                $next     = function ($request, $response) {};

                $this->middleware->__invoke($request, $response, $next);
            };
            expect($closure)->toThrow(new \Exception('a sample error preview'));

        });

        it('Error', function() {

            skipIf(PHP_MAJOR_VERSION < 7);

            try {
                $request  = Double::instance(['implements' => ServerRequestInterface::class]);
                allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('error');

                $response = Double::instance(['implements' => ResponseInterface::class]);
                $next     = function ($request, $response) {};

                $this->middleware->__invoke($request, $response, $next);
            } catch (\Throwable $error) {
                expect($error)->toBeAnInstanceOf(\Throwable::class);
            }

        });

    });

});
