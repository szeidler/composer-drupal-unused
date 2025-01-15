<?php

namespace szeidler\ComposerDrupalUnused\Helper;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigReader
 *
 * Provides methods to parse YAML files and retrieve information
 * about enabled modules from Drupal configuration.
 */
class ConfigReader
{
    /**
     * Parse a YAML file safely.
     *
     * @param string $file
     *   The YAML file path.
     * @return array
     *   The parsed YAML file.
     */
    public function parseYamlFile(string $file): array
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("File {$file} not found.");
        }

        return Yaml::parseFile($file);
    }

    /**
     * Get general and config_split enabled extensions as a combined array.
     *
     * @param string $configDirectory
     *   The Drupal config directory.
     * @return array
     *   The enabled extensions.
     */
    public function getAllEnabledExtensions(string $configDirectory): array
    {
        $enabledExtensions = $this->getEnabledExtensions($configDirectory);
        $configSplitModules = $this->getConfigSplitModules($configDirectory);

        return array_merge($enabledExtensions, $configSplitModules);
    }

    /**
     * Get enabled extensions from core.extension.yml.
     *
     * @param string $configDirectory
     *   The Drupal config directory.
     * @return array
     *   The enabled extensions.
     */
    private function getEnabledExtensions(string $configDirectory): array
    {
        $coreExtensionsFile = $configDirectory . '/core.extension.yml';
        $coreExtensions = $this->parseYamlFile($coreExtensionsFile);
        return array_merge(array_keys($coreExtensions['module']) ?? [], array_keys($coreExtensions['theme']) ?? []);
    }

    /**
     * Get Config Split enabled modules.
     *
     * @param string $directory
     *   The Drupal config directory.
     * @return array
     */
    public function getConfigSplitModules(string $directory): array
    {
        $modules = [];
        $files = glob($directory . '/config_split.config_split.*.yml') ?: []; // get all config_split ymls
        foreach ($files as $file) {
            $config = $this->parseYamlFile($file);

            // Add config split modules to the list.
            if (isset($config['module'])) {
                $modules = array_merge($modules, array_keys($config['module']));
            }
        }

        return array_unique($modules);
    }
}
