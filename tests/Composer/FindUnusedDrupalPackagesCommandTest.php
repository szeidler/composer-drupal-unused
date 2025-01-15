<?php

namespace Tests\Unit\Composer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use szeidler\ComposerDrupalUnused\Composer\FindUnusedDrupalPackagesCommand;
use szeidler\ComposerDrupalUnused\Helper\ComposerHandler;
use szeidler\ComposerDrupalUnused\Helper\ConfigReader;

class FindUnusedDrupalPackagesCommandTest extends TestCase
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

    protected function setUp(): void
    {
        // Mock ComposerHandler.
        $this->composerHandler = $this->createMock(ComposerHandler::class);
        $this->composerHandler
            ->method('getInstalledDrupalPackages')
            ->willReturn([
                'views',
                'devel',
                'debug_toolbar',
                'token', // Assume a package not present in the YAML files (unused).
            ]);

        // Mock ConfigReader.
        $this->configReader = $this->createMock(ConfigReader::class);
        $this->configReader
            ->method('getAllEnabledExtensions')
            ->with('tests/fixtures')
            ->willReturn([
                'views',
                'devel',
                'debug_toolbar', // Modules in YAML files.
            ]);
    }

    /**
     * Tests listing unused Drupal packages.
     *
     * @return void
     */
    public function testListsUnusedPackages()
    {
        $command = new FindUnusedDrupalPackagesCommand($this->configReader, $this->composerHandler);

        // Create a console input and output for the test.
        $input = new ArrayInput([
            '--config-dir' => 'tests/fixtures', // Use test fixtures.
        ]);
        $output = new BufferedOutput();

        // Execute the command.
        $exitCode = $command->run($input, $output);

        // Assert output and exit code.
        $this->assertSame(0, $exitCode);
        $content = $output->fetch();

        // Validate that unused modules are listed.
        $this->assertStringContainsString('Unused Drupal packages found:', $content);
        $this->assertStringContainsString('- token', $content);

        // Validate that used modules are not listed.
        $this->assertStringNotContainsString('- views', $content);
        $this->assertStringNotContainsString('- devel', $content);
        $this->assertStringNotContainsString('- debug_toolbar', $content);
    }

    /**
     * Tests removing unused Drupal packages.
     */
    public function testRemovesUnusedPackages()
    {
        // Mock the removePackage method to simulate the output.
        $this->composerHandler
            ->expects($this->once()) // Ensure removePackage is called once.
            ->method('removePackage')
            ->with('token') // Ensure only 'token' is passed for removal.
            ->willReturnCallback(function ($moduleName, $output) {
                // Simulate writing to the output.
                $output->writeln("Removing package: drupal/{$moduleName}");
            });

        $command = new FindUnusedDrupalPackagesCommand($this->configReader, $this->composerHandler);

        $input = new ArrayInput([
            '--config-dir' => 'tests/fixtures',
            '--remove' => true, // Include the --remove flag.
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $content = $output->fetch();

        $this->assertStringContainsString('Attempting to remove unused packages…', $content);
        $this->assertStringContainsString('Removing package: drupal/token',
            $content); // Ensure the expected string is present.
    }
}
