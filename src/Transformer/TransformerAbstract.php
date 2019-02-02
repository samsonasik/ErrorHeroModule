<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Zend\Db\Adapter\Adapter;
use Zend\Log\Logger;

abstract class TransformerAbstract
{
    private static function getWriterConfig(array $configuration) : array
    {
        return $configuration['log']['ErrorHeroModuleLogger']['writers'];
    }

    protected static function getDbAdapterConfig(array $configuration) : array
    {
        $writers = self::getWriterConfig($configuration);
        $config  = $configuration['db'];

        if (! isset($config['adapters'])) {
            return $config;
        }

        $adapterName = Adapter::class;
        foreach ($writers as $writer) {
            if ($writer['name'] === 'db') {
                $adapterName = $writer['options']['db'];
                break;
            }
        }

        if (\in_array($adapterName, \array_keys($config['adapters']), true)) {
            $config = $config['adapters'][$adapterName];
        }

        return $config;
    }

    protected static function getLoggerInstance(array $configuration, array $dbConfig) : Logger
    {
        $writers = self::getWriterConfig($configuration);
        foreach ($writers as & $writer) {
            if ($writer['name'] === 'db') {
                $writer['options']['db'] = new Adapter($dbConfig);
                break;
            }
        }

        return new Logger(['writers' => $writers]);
    }
}
