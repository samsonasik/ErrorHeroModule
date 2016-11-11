<?php

namespace ErrorHeroModule\Handler\Writer;

use Zend\Log\Writer\Db as DbWriter;

class Db
{
    private $dbWriter;
    private $configLoggingSettings;

    /**
     * @param  DbWriter    $dbWriter
     * @param  array       $configLoggingSettings
     */
    public function __construct(
        DbWriter $dbWriter,
        array $configLoggingSettings
    ) {
        $this->dbWriter              = $dbWriter;
        $this->configLoggingSettings = $configLoggingSettings;
    }

    public function checkMessageExists($message)
    {
        $currentDateTime = date('Y-m-d H:i:s');

    }
}
