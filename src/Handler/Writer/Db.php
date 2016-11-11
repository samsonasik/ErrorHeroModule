<?php

namespace ErrorHeroModule\Handler\Writer;

use ReflectionProperty;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Log\Writer\Db as DbWriter;

class Db
{
    private $dbWriter;
    private $configLoggingSettings;
    private $logWritersConfig;

    /**
     * @param  DbWriter    $dbWriter
     * @param  array       $configLoggingSettings
     * @param  array       $logWritersConfig
     */
    public function __construct(
        DbWriter $dbWriter,
        array $configLoggingSettings,
        array $logWritersConfig
    ) {
        $this->dbWriter              = $dbWriter;
        $this->configLoggingSettings = $configLoggingSettings;
        $this->logWritersConfig      = $logWritersConfig;
    }

    /**
     * @param  string          $errorFile
     * @param  int             $errorLine
     * @param  string          $errorMessage
     * @param  string          $urlError
     * @return bool
     */
    public function isExists($errorFile, $errorLine, $errorMessage, $errorUrl)
    {
        $timeRange       = $this->configLoggingSettings['same-error-log-time-range'];

        // db definition
        $reflectionProperty = new ReflectionProperty($this->dbWriter, 'db');
        $reflectionProperty->setAccessible(true);
        $db  = $reflectionProperty->getValue($this->dbWriter);

        foreach ($this->logWritersConfig as $writerConfig) {
            if ($writerConfig['name'] === 'db') {
                // table definition
                $table = $writerConfig['options']['table'];

                // columns definition
                $timestamp = $writerConfig['options']['column']['timestamp'];
                $message   = $writerConfig['options']['column']['message'];
                $file      = $writerConfig['options']['column']['extra']['file'];
                $line      = $writerConfig['options']['column']['extra']['line'];
                $url       = $writerConfig['options']['column']['extra']['url'];

                $tableGateway = new TableGateway($table, $db, null,new HydratingResultSet());
                $select       = $tableGateway->getSql()->select();
                $select->where([
                    $message => $errorMessage,
                    $line    => $errorLine,
                    $url     => $errorUrl
                ]);
                $select->order($timestamp . ' DESC');
                $select->limit(2);

                $result = $tableGateway->selectWith($select);
                if ($result->count() === 2) {
                    $resultArray = $result->toArray();
                    $last  = $resultArray[0][$timestamp];
                    $first = $resultArray[1][$timestamp];

                    $diff = strtotime($last) - strtotime($first);
                    if ($diff <= $timeRange) {
                        return true;
                    }
                }

                return false;
            }
        }

        return false;
    }
}
