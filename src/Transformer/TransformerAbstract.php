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
        $adapterName = Adapter::class;
        $writers     = self::getWriterConfig($configuration);
        $config      = $configuration['db'];

        foreach ($writers as $key => $writer) {
            if ($writer['name'] === 'db') {
                $adapterName = $writer['options']['db'];
                break;
            }
        }

        if (isset($config['adapters'])) {
            foreach ($config['adapters'] as $key => $adapterConfig) {
                if ($adapterName === $key) {
                    $config = $adapterConfig;
                    break;
                }
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