<?php

namespace ErrorHeroModule\Handler;

use Error;
use Exception;
use Zend\Log\Logger;

class Logging
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param  Exception|Error $e
     */
    public function handle($e)
    {

    }
}
