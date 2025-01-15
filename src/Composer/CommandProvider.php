<?php

namespace szeidler\ComposerDrupalUnused\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use szeidler\ComposerDrupalUnused\Helper\ComposerHandler;
use szeidler\ComposerDrupalUnused\Helper\ConfigReader;

class CommandProvider implements CommandProviderCapability
{

    private Composer $composer;

    private IOInterface $io;

    public function __construct(array $config)
    {
        $this->composer = $config['composer'];
        $this->io = $config['io'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        // Instantiate your helper services
        $configReader = new ConfigReader();
        $composerHandler = new ComposerHandler($this->composer, $this->io);
        return [
            new FindUnusedDrupalPackagesCommand($configReader, $composerHandler),
        ];
    }
}
