<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Mvc\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

describe('Integration via ErrorPreviewConsoleCommand', function (): void {

    given('application', function () {

        $application = Application::init([
            'modules' => [
                'Laminas\Router',
                'Laminas\Db',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload-for-specific-error-and-exception/error-hero-module.local.php',
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

    describe('error-preview error excluded', function(): void {

        it('show error', function(): void {

            try {
                /** @var ErrorPreviewConsoleCommand $command  */
                $command = $this->application->getServiceManager()->get(ErrorPreviewConsoleCommand::class);

                $commandTester = new CommandTester($command);
                $commandTester->execute([
                    'type' => 'error',
                ]);

            } catch (Throwable $throwable) {
                expect($throwable->getMessage())->toBe('a sample error preview');
            }

        });
    });

});
