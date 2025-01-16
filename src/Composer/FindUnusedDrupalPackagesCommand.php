<?php

namespace szeidler\ComposerDrupalUnused\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use szeidler\ComposerDrupalUnused\Helper\ComposerHandler;
use szeidler\ComposerDrupalUnused\Helper\ConfigReader;

class FindUnusedDrupalPackagesCommand extends BaseCommand
{

    /**
     * The Drupal config reader.
     *
     * @var ConfigReader
     */
    private ConfigReader $configReader;

    /**
     * The Composer handler.
     *
     * @var ComposerHandler
     */
    private ComposerHandler $composerHandler;

    /**
     * Constructs a new FindUnusedDrupalPackagesCommand.
     *
     * @param ConfigReader $configReader
     *   The Drupal config reader.
     * @param ComposerHandler $composerHandler
     *   The Composer handler.
     */
    public function __construct(ConfigReader $configReader, ComposerHandler $composerHandler)
    {
        parent::__construct();
        $this->configReader = $configReader;
        $this->composerHandler = $composerHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unused-drupal-packages')
            ->setDescription('Lists or removes unused Drupal Composer packages.')
            ->addOption('config-dir', null, InputOption::VALUE_OPTIONAL, 'Configuration directory.', 'config/sync')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove unused dependencies automatically.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configDir = $input->getOption('config-dir');
        $removeUnused = $input->getOption('remove');

        $output->writeln("<info>Using configuration directory: {$configDir}</info>");

        try {
            // Fetch enabled extensions from ConfigReader.
            $enabledExtensions = $this->configReader->getAllEnabledExtensions($configDir);

            // Fetch installed modules from ComposerService.
            $installedDrupalPackages = $this->composerHandler->getInstalledDrupalPackages();

            // Determine unused packages.
            $unusedPackages = array_diff($installedDrupalPackages, $enabledExtensions);

            if (empty($unusedPackages)) {
                $output->writeln("<info>All installed Drupal packages are in use.</info>");
                return 0;
            }

            // Display unused packages.
            $output->writeln("<comment>Unused Drupal packages found:</comment>");
            foreach ($unusedPackages as $module) {
                $output->writeln("- {$module}");
            }

            // Handle auto-removal of unused packages.
            if ($removeUnused) {
                $output->writeln("<info>Attempting to remove unused packages…</info>");
                $this->composerHandler->removePackages($unusedPackages, $output);
            }

            return 0;
        } catch (\Exception $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return 1;
        }
    }
}
