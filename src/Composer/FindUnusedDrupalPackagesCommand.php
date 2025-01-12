<?php

namespace szeidler\ComposerDrupalUnused\Composer;

use Composer\Command\BaseCommand;
use Composer\Package\CompletePackageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class FindUnusedDrupalPackagesCommand extends BaseCommand
{
  protected function configure()
  {
    $this
      ->setName('unused-drupal-packages')
      ->setDescription('Returns a list of Drupal Composer Packages that are not installed in your Drupal installation.')
      ->addOption(
        'config-dir',
        null,
        InputOption::VALUE_OPTIONAL,
        'The directory where configuration files are stored.',
        'config/sync'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $configDirectory = $input->getOption('config-dir');

    $output->writeln("<info>Using configuration directory: {$configDirectory}</info>");

    $coreExtensionsFile = $configDirectory . '/core.extension.yml';

    if (!file_exists($coreExtensionsFile)) {
      echo "Core extensions configuration file not found at {$coreExtensionsFile}.\n";
      return 1;
    }

    // Load core.extension.yml
    $coreExtensions = Yaml::parseFile($coreExtensionsFile);
    $enabledModules = $coreExtensions['module'] ?? [];

    // Check config_split modules
    $configSplitModules = $this->getConfigSplitModules($configDirectory);

    // Combine modules from core.extension and config_split
    $allEnabledModules = array_merge($enabledModules, $configSplitModules);

    // Get installed Drupal modules via Composer API
    $installedModules = $this->getInstalledDrupalModules();

    // Find missing modules
    $missingModules = array_diff($installedModules, array_keys($allEnabledModules));

    if (!empty($missingModules)) {
      echo "The following Drupal modules are not enabled in core.extension.yml or any config_split:\n";
      foreach ($missingModules as $module) {
        echo "- $module\n";
      }
    } else {
      echo "All first-level Drupal modules are enabled.\n";
    }

    return 0;
  }

  private function getInstalledDrupalModules(): array
  {
    $composer = $this->requireComposer();
    $installedRepo = $composer->getRepositoryManager()->getLocalRepository();
    $modules = [];

    /** @var CompletePackageInterface $package */
    foreach ($installedRepo->getPackages() as $package) {
      if (strpos($package->getName(), 'drupal/') === 0 && $package->getType() === 'drupal-module') {
        $moduleName = substr($package->getName(), 7); // Remove "drupal/" prefix
        $modules[] = $moduleName;
      }
    }

    return $modules;
  }

  private function getConfigSplitModules(string $directory): array
  {
    $modules = [];

    if (!is_dir($directory)) {
      echo "Configuration directory {$directory} not found.\n";
      return $modules;
    }

    $files = glob($directory . '/config_split.config_split.*.yml');

    foreach ($files as $file) {
      $config = Yaml::parseFile($file);

      if (isset($config['module'])) {
        foreach (array_keys($config['module']) as $moduleName) {
          $modules[$moduleName] = true;
        }
      }
    }

    return $modules;
  }
}
