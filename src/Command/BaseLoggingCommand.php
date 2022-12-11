<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\HeroTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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

    /**
     * Called after __construct(), as service extends this base class may use depedendency injection
     *
     * When using `Laminas\ServiceManager`, you don't need to do anything \m/, there is `initializers` config already for it.
     */
    public function init(array $errorHeroModuleConfig, Logging $logging): void
    {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging = $logging;

        /**
         * Handle overlap Fatal Error too early
         */
        $this->phpError();
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

    private function showDefaultConsoleView(OutputInterface $output = null): int
    {
        if (! $output instanceof OutputInterface) {
            return 1;
        }

        $table = new Table($output);
        $table->setColumnMaxWidth(0, 147);
        $table
            ->setRows([
                [$this->errorHeroModuleConfig[self::DISPLAY_SETTINGS]['console']['message']],
            ])
        ;
        $table->render();

        return 1;
    }
}
