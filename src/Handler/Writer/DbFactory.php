<?php

namespace ErrorHeroModule\Handler\Writer;

use RuntimeException;
use Zend\Log\Writer\Db as DbWriter;

class DbFactory
{
    public function __invoke($container)
    {
        $logger = $container->get('ErrorHeroModuleLogger');
        $writers = $logger->getWriters();
        foreach ($writers as $writer) {
            if ($writer instanceof DbWriter) {
                return new Db(
                    $writer,
                    $container->get('config')['error-hero-module']['logging-settings']
                );
            }
        }

        throw RuntimeException(Db::class ' handler cannot be created, make sure you configured the "db" writer in the ErrorHeroModuleLogger config');
    }
}
