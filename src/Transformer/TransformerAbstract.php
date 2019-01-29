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
        foreach ($writers as $key => $writer) {
            if ($writer['name'] === 'db') {
                $adapterName = $writer['options']['db'];
                break;
            }
        }

        foreach ($config['adapters'] as $key => $adapterConfig) {
            if ($adapterName === $key) {
                $config = $adapterConfig;
                break;
            }
        }

        return $config;
    }

    protected static function getLoggerInstance(array $configuration, array $dbConfig) : Logger
    {
        $writers = self::getWriterConfig($configuration);
        foreach ($writers as $key => $writer) {
            if ($writer['name'] === 'db') {
                $writers[$key]['options']['db'] = new Adapter($dbConfig);
                break;
            }
        }

        return new Logger(['writers' => $writers]);
    }
}