<?php

declare(strict_types=1);

namespace ErrorHeroModule\Spec\Fixture;

use Laminas\Cache\ConfigProvider as LaminasCacheConfigProvider;
use Laminas\Cache\Storage\Adapter\Filesystem\AdapterPluginManagerDelegatorFactory as FilesystemAdapterPluginManagerDelegatorFactory;
use Laminas\Cache\Storage\Adapter\Memory\AdapterPluginManagerDelegatorFactory as MemoryAdapterPluginManagerDelegatorFactory;
use Laminas\Cache\Storage\AdapterPluginManager;

final class LaminasCacheModule
{
    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        $configProvider = new LaminasCacheConfigProvider();

        // laminas-cache exposes its ServiceManager wiring under the `dependencies` key.
        // Laminas MVC consumes `service_manager`, so we map it here.
        //
        // Also include the installed adapter packages' delegators so the AdapterPluginManager
        // knows how to create the configured adapters (e.g. Memory, Filesystem).
        $serviceManagerConfig = $configProvider->getDependencyConfig();
        $serviceManagerConfig['delegators'][AdapterPluginManager::class] ??= [];
        $serviceManagerConfig['delegators'][AdapterPluginManager::class] = array_values(array_unique(array_merge(
            $serviceManagerConfig['delegators'][AdapterPluginManager::class],
            [
                MemoryAdapterPluginManagerDelegatorFactory::class,
                FilesystemAdapterPluginManagerDelegatorFactory::class,
            ]
        )));

        return [
            'service_manager' => $serviceManagerConfig,
        ];
    }
}
