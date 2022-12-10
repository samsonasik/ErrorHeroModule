<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Laminas\Text\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function ErrorHeroModule\isExcludedException;

abstract class BaseLoggingCommand extends Command
{
    use HeroTrait;

    /** @var string */
    private const DISPLAY_SETTINGS = 'display-settings';

    private array $errorHeroModuleConfig;

    private Logging $logging;

    private ?OutputInterface $output = null;

    public function init(array $errorHeroModuleConfig, Logging $logging): void
    {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging = $logging;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        try {
            $this->phpError();
            return parent::run($input, $output);
        } catch (Throwable $throwable) {
        }

        return $this->exceptionError($throwable);
    }

    private function exceptionError(Throwable $throwable): int
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
        return $this->showDefaultConsoleView($this->output);
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
