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
     * Remove multiple Drupal packages.
     *
     * @param array $moduleNames
     *   Array of module names (without 'drupal/' prefix).
     * @param OutputInterface $output
     *   Console output interface.
     */
    public function removePackages(array $moduleNames, OutputInterface $output): void
    {
        $packageNames = array_map(fn($name) => "drupal/{$name}", $moduleNames);

        try {
            $output->writeln("<info>Removing packages: " . implode(', ', $packageNames) . "</info>");

            // Get root package requirements.
            $rootPackage = $this->composer->getPackage();
            $requires = $rootPackage->getRequires();
            $devRequires = $rootPackage->getDevRequires();

            // Remove each package from `require` or `require-dev`.
            foreach ($packageNames as $packageName) {
                if (array_key_exists($packageName, $requires)) {
                    unset($requires[$packageName]);
                }
                if (array_key_exists($packageName, $devRequires)) {
                    unset($devRequires[$packageName]);
                }
            }

            $rootPackage->setRequires($requires);
            $rootPackage->setDevRequires($devRequires);

            // Update composer.json using an internal helper method.
            $this->writeComposerJson($rootPackage);

            // Run the Composer installer to remove specified packages.
            $installer = Installer::create($this->io, $this->composer);
            $installer->setUpdate(true)
                ->setDevMode(true) // Important to handle dev dependencies correctly.
                ->setUpdateAllowList($packageNames); // Remove only specified packages.

            $status = $installer->run();

            if ($status !== 0) {
                $output->writeln("<error>Failed to remove packages: " . implode(', ', $packageNames) . "</error>");
            } else {
                $output->writeln("<info>Successfully removed packages: " . implode(', ', $packageNames) . "</info>");
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Error removing packages: " . implode(
                ', ',
                $packageNames
            ) . ". {$e->getMessage()}</error>");
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
