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
              'message' => '1: a sample error preview',
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
                      'query' => [
                        'foo' => 'bar',
                        'page' => '1',
                      ],
                      'request_method' => 'GET',
                      'body_data' => [],
                      'raw_data' => '',
                      'files_data' => [],
                    ],
                ],
            ];

            expect('json_encode')->toBeCalled();

            $formatter = new Json();
            $actual = $formatter->format($event);

            expect($actual)->toBe("{\n    \"timestamp\": \"2016-12-30T00:42:49+07:00\",\n    \"priority\": 3,\n    \"priorityName\": \"ERR\",\n    \"message\": \"1: a sample error preview\",\n    \"extra\": {\n        \"url\": \"http://localhost/error-preview?foo=bar&page=1\",\n        \"file\": \"/path/to/app/vendor/samsonasik/error-hero-module/src/Controller/ErrorPreviewController.php\",\n        \"line\": 11,\n        \"error_type\": \"Exception\",\n        \"trace\": \"#0 /path/to/app/vendor/zendframework/zend-mvc/src/Controller/AbstractActionController.php(78): ErrorHeroModule\\\\Controller\\\\ErrorPreviewController->exceptionAction()\n                                #1 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(322): Zend\\\\Mvc\\\\Controller\\\\AbstractActionController->onDispatch(Object(Zend\\\\Mvc\\\\MvcEvent))\n                                #2 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(179): Zend\\\\EventManager\\\\EventManager->triggerListeners(Object(Zend\\\\Mvc\\\\MvcEvent), Object(Closure))\n                                #3 /path/to/app/vendor/zendframework/zend-mvc/src/Controller/AbstractController.php(105): Zend\\\\EventManager\\\\EventManager->triggerEventUntil(Object(Closure), Object(Zend\\\\Mvc\\\\MvcEvent))\n                                #4 /path/to/app/vendor/zendframework/zend-mvc/src/DispatchListener.php(119): Zend\\\\Mvc\\\\Controller\\\\AbstractController->dispatch(Object(Zend\\\\Http\\\\PhpEnvironment\\\\Request), Object(Zend\\\\Http\\\\PhpEnvironment\\\\Response))\n                                #5 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(322): Zend\\\\Mvc\\\\DispatchListener->onDispatch(Object(Zend\\\\Mvc\\\\MvcEvent))\n                                #6 /path/to/app/vendor/zendframework/zend-eventmanager/src/EventManager.php(179): Zend\\\\EventManager\\\\EventManager->triggerListeners(Object(Zend\\\\Mvc\\\\MvcEvent), Object(Closure))\n                                #7 /path/to/app/vendor/zendframework/zend-mvc/src/Application.php(332): Zend\\\\EventManager\\\\EventManager->triggerEventUntil(Object(Closure), Object(Zend\\\\Mvc\\\\MvcEvent))\n                                #8 /path/to/app/public/index.php(40): Zend\\\\Mvc\\\\Application->run()\n                                #9 {main}\",\n        \"request_data\": {\n            \"query\": {\n                \"foo\": \"bar\",\n                \"page\": \"1\"\n            },\n            \"request_method\": \"GET\",\n            \"body_data\": [],\n            \"raw_data\": \"\",\n            \"files_data\": []\n        }\n    }\n}");

        });

    });

});
