<?php

namespace ErrorHeroModule\Spec\Integration;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Controller\ErrorPreviewConsoleController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Laminas\Console\Console;
use Laminas\Console\Request;
use Laminas\Mvc\Application;

describe('Integration via ErrorPreviewConsoleController with doctrine', function () {

    given('application', function () {

        Console::overrideIsConsole(true);

        $application = Application::init([
            'modules' => [
                'Laminas\Router',
                'DoctrineModule',
                'DoctrineORMModule',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload-with-doctrine/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);
        $application->getMvcEvent()->setRequest(new Request());

        $serviceManager = $application->getServiceManager();
        $entityManager  = $serviceManager->get(EntityManager::class);
        $stmt = $entityManager->getConnection()->prepare('DELETE FROM log');
        $stmt->execute();

        return $application;

    });

    describe('error-preview', function() {

        it('show error page', function() {

            Quit::disable();

            $_SERVER['argv'] = [
                __FILE__,
                'error-preview',
                'controller' => ErrorPreviewConsoleController::class,
                'action' => 'exception',
            ];

            \ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = \ob_get_clean();

            expect($content)->toContain('|We have encountered a problem and we can not fulfill your request');

        });

    });

    describe('error-preview error', function() {

        it('show error page', function() {

            Quit::disable();

            $_SERVER['argv'] = [
                __FILE__,
                'error-preview',
                'controller' => ErrorPreviewConsoleController::class,
                'action' => 'error',
            ];

            \ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = \ob_get_clean();

            expect($content)->toContain('|We have encountered a problem and we can not fulfill your request');

        });
    });

});
