<?php

namespace ErrorHeroModule\Spec\Integration;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand;
use Laminas\Mvc\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('Integration via ErrorPreviewConsoleController with doctrine', function (): void {

    given('application', function () {

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

        $serviceManager = $application->getServiceManager();
        $entityManager  = $serviceManager->get(EntityManager::class);
        $stmt = $entityManager->getConnection()->prepare('DELETE FROM log');
        $stmt->execute();

        return $application;

    });

    describe('error-preview', function(): void {

        it('show error page', function(): void {

            /** @var ErrorPreviewConsoleCommand $command  */
            $command = $this->application->getServiceManager()->get(ErrorPreviewConsoleCommand::class);

            $commandTester = new CommandTester($command);
            $commandTester->execute([]);

            expect($commandTester->getDisplay())->toContain('| We have encountered a problem and we can not fulfill your request');

        });

    });

    describe('error-preview error', function(): void {

        it('show error page', function(): void {

            /** @var ErrorPreviewConsoleCommand $command  */
            $command = $this->application->getServiceManager()->get(ErrorPreviewConsoleCommand::class);

            $commandTester = new CommandTester($command);
            $commandTester->execute([
                'type' => 'error',
            ]);

            expect($commandTester->getDisplay())->toContain('| We have encountered a problem and we can not fulfill your request');

        });
    });

});
