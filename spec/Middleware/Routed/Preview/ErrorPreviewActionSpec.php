<?php

namespace ErrorHeroModule\Spec\Middleware\Routed\Preview;

use Error;
use ErrorException;
use ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

describe('ErrorPreviewAction', function () {

    given('middleware', function () {
        return new ErrorPreviewAction();
    });

    describe('->process()', function () {

        it('throw Exception', function () {

            $closure = function () {
                $request  = Double::instance(['implements' => ServerRequestInterface::class]);
                allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('exception');

                $handler = Double::instance(['implements' => RequestHandlerInterface::class]);

                $this->middleware->process($request, $handler);
            };
            expect($closure)->toThrow(new \Exception('a sample exception preview'));

        });

        it('PHP E_* Error: Notice', function() {

            $request  = Double::instance(['implements' => ServerRequestInterface::class]);
            allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('PHP E_* Error: Notice');

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);

            $closure = function () use ($request, $handler) {
                $this->middleware->process($request, $handler);
            };
            $exception = new ErrorException('Undefined offset: 1', 500);
            expect($closure)->toThrow($exception);

        });

        it('PHP7 Error', function() {

            $request  = Double::instance(['implements' => ServerRequestInterface::class]);
            allow($request)->toReceive('getAttribute')->with('action', 'exception')->andReturn('error');

            $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);

            $closure = function () use ($request, $handler) {
                $this->middleware->process($request, $handler);
            };

            expect($closure)->toThrow(new Error('a sample error preview'));

        });

    });

});
