<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler\Writer\Checker;

use Closure;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Log\Writer\Db as DbWriter;

use function date;
use function strtotime;

class Db
{
    /** @var string */
    private const OPTIONS = 'options';

    /** @var string */
    private const COLUMN = 'column';

    /** @var string */
    private const EXTRA = 'extra';

    public function __construct(
        private DbWriter $dbWriter,
        private array $configLoggingSettings,
        private array $logWritersConfig
    ) {
    }

    public function isExists(
        string $errorFile,
        int $errorLine,
        string $errorMessage,
        string $errorUrl,
        string $errorType
    ): bool {
        // db definition
        $db = Closure::bind(static fn($dbWriter) => $dbWriter->db, null, $this->dbWriter)($this->dbWriter);

        foreach ($this->logWritersConfig as $logWriterConfig) {
            if ($logWriterConfig['name'] === 'db') {
                // table definition
                $table = $logWriterConfig[self::OPTIONS]['table'];

                // columns definition
                $timestamp  = $logWriterConfig[self::OPTIONS][self::COLUMN]['timestamp'];
                $message    = $logWriterConfig[self::OPTIONS][self::COLUMN]['message'];
                $file       = $logWriterConfig[self::OPTIONS][self::COLUMN][self::EXTRA]['file'];
                $line       = $logWriterConfig[self::OPTIONS][self::COLUMN][self::EXTRA]['line'];
                $url        = $logWriterConfig[self::OPTIONS][self::COLUMN][self::EXTRA]['url'];
                $error_type = $logWriterConfig[self::OPTIONS][self::COLUMN][self::EXTRA]['error_type'];

                $tableGateway = new TableGateway($table, $db, null, new ResultSet());
                $select       = $tableGateway->getSql()->select();
                $select->columns([$timestamp]);
                $select->where([
                    $message    => $errorMessage,
                    $line       => $errorLine,
                    $url        => $errorUrl,
                    $file       => $errorFile,
                    $error_type => $errorType,
                ]);
                $select->order($timestamp . ' DESC');
                $select->limit(1);

                /** @var ResultSet $result */
                $result = $tableGateway->selectWith($select);
                if (! ($current = $result->current())) {
                    return false;
                }

                $first = $current[$timestamp];
                $last  = date('Y-m-d H:i:s');

                $diff = strtotime($last) - strtotime($first);
                if ($diff <= $this->configLoggingSettings['same-error-log-time-range']) {
                    return true;
                }

                break;
            }
        }

        return false;
    }
}
