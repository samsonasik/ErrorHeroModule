<?php

namespace ErrorHeroModule\Spec\Middleware\Routed\Preview;

use ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

            try {
                $request  = Double::instance(['implements' => ServerRequestInterface::class]);
                allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('error');

                $response = Double::instance(['implements' => ResponseInterface::class]);
                $next     = function ($request, $response) {};

                $this->middleware->__invoke($request, $response, $next);
            } catch (\Throwable $error) {
                expect($error)->toBeAnInstanceOf(\Throwable::class);
                expect($error->getMessage())->toContain('Undefined offset: 1');
            }

        });

        it('PHP7 Error', function() {

            try {
                $request  = Double::instance(['implements' => ServerRequestInterface::class]);
                allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('php7error');

                $response = Double::instance(['implements' => ResponseInterface::class]);
                $next     = function ($request, $response) {};

                $this->middleware->__invoke($request, $response, $next);
            } catch (\Throwable $error) {
                expect($error)->toBeAnInstanceOf(\Throwable::class);
                expect($error->getMessage())->toContain('error of php 7');
            }

        });

    });

});
