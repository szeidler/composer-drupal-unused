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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        // Mock ComposerHandler.
        $this->composerHandler = $this->createMock(ComposerHandler::class);

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
     */
    public function testListsUnusedPackages()
    {
        $this->composerHandler
            ->method('getInstalledDrupalPackages')
            ->willReturn([
                'views',
                'devel',
                'debug_toolbar',
                'token', // Assume a package not present in the YAML files (unused).
            ]);

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
     * Test when all installed packages are in use.
     */
    public function testNoUnusedPackages()
    {
        $this->composerHandler
            ->method('getInstalledDrupalPackages')
            ->willReturn(['views', 'devel', 'debug_toolbar']); // All packages are in use.

        $command = new FindUnusedDrupalPackagesCommand($this->configReader, $this->composerHandler);

        $input = new ArrayInput([
            '--config-dir' => 'tests/fixtures',
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);

        $content = $output->fetch();
        $this->assertStringContainsString('All installed Drupal packages are in use.', $content);
        $this->assertStringNotContainsString('Unused Drupal packages found:', $content);
    }


    /**
     * Tests the removal of unused Drupal packages.
     */
    public function testRemovesUnusedPackages()
    {
        $this->composerHandler
            ->method('getInstalledDrupalPackages')
            ->willReturn([
                'views',
                'devel',
                'debug_toolbar',
                'token', // Assume a package not present in the YAML files (unused).
            ]);

        $this->composerHandler
            ->expects($this->once())
            ->method('removePackages')
            ->with([3 => 'token']); // Expect only the unused package 'token' to be passed.

        // Create the command with mocked dependencies.
        $command = new FindUnusedDrupalPackagesCommand($this->configReader, $this->composerHandler);

        // Create a console input and output for the test.
        $input = new ArrayInput([
            '--config-dir' => 'tests/fixtures', // Use test fixtures.
            '--remove' => true, // Indicate removal should occur.
        ]);
        $output = new BufferedOutput();

        // Execute the command.
        $exitCode = $command->run($input, $output);

        // Assert exit code.
        $this->assertSame(0, $exitCode);

        // Fetch the output and validate that unused packages were identified.
        $content = $output->fetch();
        $this->assertStringContainsString('Unused Drupal packages found:', $content);
        $this->assertStringContainsString('- token', $content);
        $this->assertStringContainsString('Attempting to remove unused packages…', $content);

        // Validate that used modules are not listed for removal.
        $this->assertStringNotContainsString('- views', $content);
        $this->assertStringNotContainsString('- devel', $content);
        $this->assertStringNotContainsString('- debug_toolbar', $content);
    }

    /**
     * Test invalid config directory.
     */
    public function testInvalidConfigDirectory()
    {
        $this->configReader
            ->method('getAllEnabledExtensions')
            ->willThrowException(new \RuntimeException('Invalid config directory.'));

        $command = new FindUnusedDrupalPackagesCommand($this->configReader, $this->composerHandler);

        $input = new ArrayInput([
            '--config-dir' => 'invalid/directory',
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);

        $content = $output->fetch();
        $this->assertStringContainsString('Error: Invalid config directory.', $content);
    }

    /**
     * Test removal of packages not triggered without passing the `--remove` option.
     */
    public function testDoesNotRemoveWithoutFlag()
    {
        $this->composerHandler
            ->method('getInstalledDrupalPackages')
            ->willReturn([
                'views',
                'devel',
                'debug_toolbar',
                'token', // Assume a package not present in the YAML files (unused).
            ]);

        $this->composerHandler
            ->expects($this->never())
            ->method('removePackages');

        $command = new FindUnusedDrupalPackagesCommand($this->configReader, $this->composerHandler);

        $input = new ArrayInput([
            '--config-dir' => 'tests/fixtures',
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);

        $content = $output->fetch();
        $this->assertStringContainsString('Unused Drupal packages found:', $content);
    }
}
