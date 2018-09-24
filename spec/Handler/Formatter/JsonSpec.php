<?php

namespace ErrorHeroModule\Spec\Handler\Formatter;

use DateTime;
use ErrorHeroModule\Handler\Formatter\Json;

describe('Json', function () {

    describe('->format()', function () {

        it('format json', function () {

            $event = [
              'timestamp' => DateTime::__set_state([
                 'date' => '2016-12-30 00:42:49.558706',
                 'timezone_type' => 3,
                 'timezone' => 'Asia/Jakarta',
              ]),
              'priority' => 3,
              'priorityName' => 'ERR',
              'message' => '1: a sample exception preview',
              'extra' => [
                    'url' => 'http://localhost/error-preview?foo=bar&page=1',
                    'file' => '/path/to/app/vendor/samsonasik/error-hero-module/src/Controller/ErrorPreviewController.php',
                    'line' => 11,
                    'error_type' => 'Exception',
                    'trace' => '#0 /path/to/app/vendor/zendframework/zend-mvc/src/Controller/AbstractActionController.php(78): ErrorHeroModule\\Controller\\ErrorPreviewController->exceptionAction()
                                #1 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(322): Zend\\Mvc\\Controller\\AbstractActionController->onDispatch(Object(Zend\\Mvc\\MvcEvent))
                                #2 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(179): Zend\\EventManager\\EventManager->triggerListeners(Object(Zend\\Mvc\\MvcEvent), Object(Closure))
                                #3 /path/to/app/vendor/zendframework/zend-mvc/src/Controller/AbstractController.php(105): Zend\\EventManager\\EventManager->triggerEventUntil(Object(Closure), Object(Zend\\Mvc\\MvcEvent))
                                #4 /path/to/app/vendor/zendframework/zend-mvc/src/DispatchListener.php(119): Zend\\Mvc\\Controller\\AbstractController->dispatch(Object(Zend\\Http\\PhpEnvironment\\Request), Object(Zend\\Http\\PhpEnvironment\\Response))
                                #5 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(322): Zend\\Mvc\\DispatchListener->onDispatch(Object(Zend\\Mvc\\MvcEvent))
                                #6 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(179): Zend\\EventManager\\EventManager->triggerListeners(Object(Zend\\Mvc\\MvcEvent), Object(Closure))
                                #7 /path/to/app/vendor/zendframework/zend-mvc/src/Application.php(332): Zend\\EventManager\\EventManager->triggerEventUntil(Object(Closure), Object(Zend\\Mvc\\MvcEvent))
                                #8 /path/to/app/public/index.php(40): Zend\\Mvc\\Application->run()
                                #9 {main}',
                    'request_data' => [
                      'request_method' => 'GET',
                      'query_data' => [
                        'foo' => 'bar',
                        'page' => '1',
                      ],
                      'body_data' => [],
                      'raw_data' => '',
                      'files_data' => [],
                      'cookie_data' => [],
                      'ip_address'  => '10.1.1.1',
                    ],
                ],
            ];

            $actualOld = (new Json())->format($event);

            // idempotent format call will use old timestamp
            $event['timestamp'] = DateTime::__set_state([
                'date' => '2016-12-30 00:42:55.558706',
                'timezone_type' => 3,
                'timezone' => 'Asia/Jakarta',
            ]);

            $actualNew = (new Json())->format($event);
            expect($actualNew)->toBe($actualOld);

        });

    });

});
