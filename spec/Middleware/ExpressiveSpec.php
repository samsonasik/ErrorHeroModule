<?php

namespace ErrorHeroModule\Spec\Middleware;

use ErrorHeroModule\Middleware\Expressive;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

describe('Expressive', function () {

    given('middleware', function () {
        return new Expressive();
    });

    describe('->__invoke()', function () {

        it('returns next() when no error', function () {

            $request  = Double::instance(['implements' => ServerRequestInterface::class]);
            $response = Double::instance(['implements' => ResponseInterface::class]);
            $next     = function ($request, $response) {
                return new Response();
            };

            $actual = $this->middleware->__invoke($request, $response, $next);
            expect($actual)->toBeAnInstanceOf(Response::class);

        });

    });

});
