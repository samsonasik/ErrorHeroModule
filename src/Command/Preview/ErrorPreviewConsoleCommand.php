<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command\Preview;

use Error;
use ErrorHeroModule\Command\BaseLoggingCommand;
use Exception;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ErrorPreviewConsoleCommand extends BaseLoggingCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of preview: exception, error, warning, fatal');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');

        if ($type === null) {
            throw new Exception('a sample exception preview');
        }

        if ($type === 'error') {
            throw new Error('a sample error preview');
        }

        if ($type === 'warning') {
            $array = [];
            $array[1]; // E_WARNING
        }

        if ($type === 'fatal') {
            $y = new class implements stdClass {
            };
        }

        return 0;
    }
}
