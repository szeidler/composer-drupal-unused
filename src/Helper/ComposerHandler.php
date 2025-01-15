<?php

namespace szeidler\ComposerDrupalUnused\Helper;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackageInterface;
use Composer\Package\RootPackageInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Handles Composer operations related to Drupal modules.
 */
class ComposerHandler
{
    /**
     * The composer runner.
     *
     * @var Composer
     */
    private Composer $composer;

    /**
     * The IO.
     *
     * @var IOInterface
     */
    private IOInterface $io;

    /**
     * Constructs a new ComposerHandler
     *
     * @param Composer $composer
     *   The composer runner.
     * @param IOInterface $io
     *   The IO.
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Get the list of all installed Drupal packages.
     *
     * @return array
     *   List of package names (without 'drupal/' prefix)
     */
    public function getInstalledDrupalPackages(): array
    {
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $extensions = [];

        /** @var CompletePackageInterface $package */
        foreach ($localRepository->getPackages() as $package) {
            if ($package->getType() === 'drupal-module') {
                // Extract extension name from the package name.
                [$namespace, $packageName] = explode('/', $package->getName(), 2);
                $extensions[] = $packageName;
            }
        }

        return $extensions;
    }

    /**
     * Remove a specific Drupal package.
     *
     * @param string $moduleName
     *   Name of the Drupal module (without 'drupal/' prefix)
     * @param OutputInterface $output
     *   Console output interface
     */
    public function removePackage(string $moduleName, OutputInterface $output): void
    {
        $packageName = "drupal/{$moduleName}";

        try {
            $output->writeln("<info>Removing package: {$packageName}</info>");

            // Check if the module exists in composer.json dependencies
            $rootPackage = $this->composer->getPackage();
            $requires = $rootPackage->getRequires();

            if (!array_key_exists($packageName, $requires)) {
                $output->writeln("<comment>Package {$packageName} is not found in composer.json requirements.</comment>");
                return;
            }

            // Remove the package from dependencies
            unset($requires[$packageName]);
            $rootPackage->setRequires($requires);

            // Update composer.json using an internal helper method
            $this->writeComposerJson($rootPackage);

            // Run the Composer installer to remove the module
            $installer = Installer::create($this->io, $this->composer);
            $installer->setUpdate(true)
                ->setUpdateAllowList([$packageName]) // Restrict updates to the package being removed
                ->setDevMode(false);

            $status = $installer->run();

            if ($status !== 0) {
                $output->writeln("<error>Failed to remove package: {$packageName}</error>");
            } else {
                $output->writeln("<info>Successfully removed package: {$packageName}</info>");
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Error removing package {$packageName}: {$e->getMessage()}</error>");
        }
    }

    /**
     * Update the composer.json file with the modified RootPackage.
     *
     * @param RootPackageInterface $rootPackage
     */
    private function writeComposerJson(RootPackageInterface $rootPackage): void
    {
        $jsonFilePath = $this->composer->getConfig()->get('vendor-dir') . '/../composer.json';
        $jsonFile = new JsonFile($jsonFilePath);

        // Read current composer.json data
        $composerData = $jsonFile->read();

        // Update the 'require' field with current requirements from RootPackage
        $composerData['require'] = array_map(
            fn($link) => $link->getPrettyConstraint(),
            $rootPackage->getRequires()
        );

        // Write updated composer.json back to disk
        $jsonFile->write($composerData);
    }
}
