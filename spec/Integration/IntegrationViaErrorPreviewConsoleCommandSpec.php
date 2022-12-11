<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Mvc\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('Integration via ErrorPreviewConsoleController', function (): void {

    given('application', function () {

        $application = Application::init([
            'modules' => [
                'Laminas\Router',
                'Laminas\Db',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $serviceManager = $application->getServiceManager();
        $db             = $serviceManager->get(AdapterInterface::class);
        $tableGateway   = new TableGateway('log', $db, null, new ResultSet());
        $tableGateway->delete([]);

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
