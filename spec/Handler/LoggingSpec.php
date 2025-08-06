<?php

namespace ErrorHeroModule\Spec\Handler;

use ErrorException;
use ErrorHeroModule\Handler\Logging;
use Exception;
use Kahlan\Plugin\Double;
use Laminas\Http\PhpEnvironment\Request;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

describe('LoggingSpec', function (): void {

    beforeAll(function (): void {
        $this->logger  = Double::instance(['extends' => LoggerInterface::class]);
        $this->request = new Request();
        $this->request->setUri('http://www.example.com');

        $this->errorHeroModuleLocalConfig = [
            'enable' => true,
            'display-settings' => [

                // excluded php errors
                'exclude-php-errors' => [
                    \E_USER_DEPRECATED
                ],

                // show or not error
                'display_errors'  => 0,

                // if enable and display_errors = 0, the page will bring layout and view
                'template' => [
                    'layout' => 'layout/layout',
                    'view'   => 'error-hero-module/error-default'
                ],

                // if enable and display_errors = 0, the console will bring message
                'console' => [
                    'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
                ],

            ],
            'logging-settings' => [
                'same-error-log-time-range' => 86400,
            ],
            'email-notification-settings' => [
                // set to true to activate email notification on log error
                'enable' => false,

                // DSN for mailer
                'mail-dsn' => 'smtp://localhost:25',

                // email sender
                'email-from'    => 'Sender Name <sender@host.com>',

                // to include or not $_FILES on send mail
                'include-files-to-attachments' => true,

                'email-to-send' => [
                    'developer1@foo.com',
                    'developer2@foo.com',
                ],
            ],
        ];
    });

    given('logging', fn() : Logging => new Logging(
        $this->logger,
        true
    ));

    describe('->handleErrorException()', function (): void  {

        it('not log if exists', function (): void  {
            $exception = new Exception();
            $this->logging->handleErrorException($exception, $this->request);

        });

        it('not log if exists and exception instanceof ErrorException', function (): void  {
            $exception = new ErrorException();
            $this->logging->handleErrorException($exception, $this->request);

        });
    });
});
