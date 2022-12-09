<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command;

use ErrorHeroModule\Handler\Logging;
use Laminas\Text\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function ErrorHeroModule\isExcludedException;

abstract class BaseLoggingCommand extends Command
{
    /** @var string */
    private const DISPLAY_SETTINGS = 'display-settings';

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
        } catch (Throwable $throwable) {
        }

        return $this->exceptionError($throwable, $output);
    }

    private function exceptionError(Throwable $throwable, OutputInterface $output): int
    {
        if (
            isset($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['exclude-exceptions'])
            && isExcludedException(
                $this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['exclude-exceptions'],
                $throwable
            )
        ) {
            throw $throwable;
        }

        $this->logging->handleErrorException($throwable);

        if ($this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['display_errors']) {
            throw $throwable;
        }

        // show default view if display_errors setting = 0.
        return $this->showDefaultConsoleView($output);
    }

    private function showDefaultConsoleView(OutputInterface $output): int
    {
        $table = new Table\Table([
            'columnWidths' => [150],
        ]);
        $table->setDecorator('ascii');
        $table->appendRow([$this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['console']['message']]);

        $output->writeln($table->render());
        return 1;
    }
}
