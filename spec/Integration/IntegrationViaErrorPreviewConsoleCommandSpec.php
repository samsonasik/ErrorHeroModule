<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand;
use Laminas\Mvc\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('Integration via ErrorPreviewConsoleCommand', function (): void {

    given('application', function () {

        $application = Application::init([
            'modules' => [
                'Laminas\Router',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

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
