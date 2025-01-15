# Composer Drupal Unused

The Composer Drupal Unused package returns a list of Drupal Composer Packages that are not installed in your Drupal
installation.

This plugin does not require a fully installed and boostrapped Drupal installation. It just parses the `composer.json`
and typical configuration files in Drupal.

## Requirements

* PHP 8.1 or greater
* Composer

## Installation

Add Composer Drupal Unused as a composer dependency.

```shell
composer require szeidler/composer-drupal-unused:^1.0
```

or global

```shell
composer global require szeidler/composer-drupal-unused:^1.0
```

## Usage

### List unused Drupal packages

```shell
composer unused-drupal-packages
```

The command accepts the following options.

* `--config-dir` Define the path to your config directory. Defaults to `config/sync`.
* `--remove` Removes the identified unused packages from composer.lock and composer.json.

The command will list up Drupal packages that are declared as root level dependencies your composer.json, but not
enabled in Drupal.

It will look up the `core.extension.yml` from the given Drupal Configuration Management directory. In addition it
will look up `config_split` config files to see if a module is conditionally enabled.

#### Remove option
You should be careful using the plugin using the `--remove` function. Always review the changes. There might be edge
cases where the Composer package name does not match the Drupal module machine name. The script would identify the
package as unused and therefore delete it.

## Credits

Stephan Zeidler for [Ramsalt Lab AS](https://ramsalt.com)

## License

The MIT License (MIT)
