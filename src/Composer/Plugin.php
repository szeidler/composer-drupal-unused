<?php

namespace szeidler\ComposerDrupalUnused\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable
{
  public function activate(Composer $composer, IOInterface $io)
  {
    $io->write("<info>Drupal module checker plugin activated.</info>");
  }

  public function deactivate(Composer $composer, IOInterface $io)
  {
    $io->write("<info>Drupal module checker plugin deactivated.</info>");
  }

  public function uninstall(Composer $composer, IOInterface $io)
  {
    $io->write("<info>Drupal module checker plugin uninstalled.</info>");
  }

  public function getCapabilities()
  {
    return [
      'Composer\Plugin\Capability\CommandProvider' => 'szeidler\ComposerDrupalUnused\Composer\CommandProvider',
    ];
  }
}
