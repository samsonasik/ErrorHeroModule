<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command;

use ErrorHeroModule\Handler\Logging;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class BaseLoggingCommand extends Command
{
    public function __construct(
        private array $errorHeroModuleConfig,
        private readonly Logging $logging
    ) {
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        static $isRun;

        // avoid infinite execution, ref https://3v4l.org/EbrCu
        if ($isRun === true) {
            return 0;
        }

        $isRun = true;
        try {
            return parent::run($input, $output);
        } catch (Throwable $throwable) {}

        return $this->exceptionError($throwable);
    }

    private function exceptionError(Throwable $throwable): int
    {
        return 1;
    }
}
