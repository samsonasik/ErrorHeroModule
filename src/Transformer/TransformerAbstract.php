<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Laminas\Db\Adapter\Adapter;
use Laminas\Log\Logger;

abstract class TransformerAbstract
{
    private static function getWriterConfig(array $configuration): array
    {
        return $configuration['log']['ErrorHeroModuleLogger']['writers'];
    }

    protected static function getDbAdapterConfig(array $configuration): array
    {
        $writers = self::getWriterConfig($configuration);
        $config  = $configuration['db'];

        if (! isset($config['adapters'])) {
            return $config;
        }

        foreach ($writers as $writer) {
            if ($writer['name'] === 'db') {
                $adapterName = $writer['options']['db'];
                break;
            }
        }

        return isset($adapterName)
            ? ($config['adapters'][$adapterName] ?? $config)
            : $config;
    }

    protected static function getLoggerInstance(array $configuration, array $dbConfig): Logger
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
