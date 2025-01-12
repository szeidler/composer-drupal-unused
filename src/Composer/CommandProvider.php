<?php

namespace szeidler\ComposerDrupalUnused\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
  public function getCommands()
  {
    return [
      new FindUnusedDrupalPackagesCommand(),
    ];
  }
}
