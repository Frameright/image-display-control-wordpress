# Contributing

## Running the unit tests

### Setting up PHPUnit

Install [PHPUnit](https://phpunit.readthedocs.io/en/9.5/installation.html)
with:

```bash
$ sudo apt install composer
$ composer install
```

### Running PHPUnit

Run the unit tests with:

```bash
$ composer test
```

## Formatting the code

Pull and run [prettier](https://github.com/prettier/plugin-php) with:

```bash
$ yarn install     # pull
$ composer format  # run
```

## Validating against WordPress coding standards

### Setting up PHP_CodeSniffer

First pull the
[WordPress coding standards](https://github.com/WordPress/WordPress-Coding-Standards)
in to `wpcs/` by running:

```bash
$ docker run --rm -it --volume $PWD:/app -u `id -u`:`id -g` \
    composer:1.10.19 create-project wp-coding-standards/wpcs --no-dev
```

> **NOTE**: We're using a
> [Composer Docker image](https://hub.docker.com/_/composer/) old enough to
> contain PHP 7 instead of PHP 8, as the
> [coding standards don't support PHP 8](https://github.com/WordPress/WordPress-Coding-Standards/issues/2070)
> yet.

Then configure [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
to know where to find the WordPress coding standards by running:

```bash
$ ./wpcs/vendor/bin/phpcs -i
The installed coding standards are MySource, PEAR, PSR1, PSR2, PSR12, Squiz and Zend

$ ./wpcs/vendor/bin/phpcs --config-set installed_paths wpcs/
Using config file: /.../image-display-control-wordpress/wpcs/vendor/squizlabs/php_codesniffer/CodeSniffer.conf
Config value "installed_paths" added successfully

$ ./wpcs/vendor/bin/phpcs -i
The installed coding standards are MySource, PEAR, PSR1, PSR2, PSR12, Squiz, Zend, WordPress, WordPress-Core, WordPress-Docs and WordPress-Extra
```

Finally pull the
[rulesets for PHP cross-version compatibility](https://github.com/PHPCompatibility/PHPCompatibilityWP)
with:

```bash
$ pushd wpcs/
$ docker run --rm -it --volume $PWD:/app -u `id -u`:`id -g` \
    composer:1.10.19 require --dev phpcompatibility/phpcompatibility-wp:"*"
$ popd
```

### Running PHP_CodeSniffer

Run PHP_CodeSniffer with:

```bash
$ composer lint
```

## Building the plugin as a ZIP file

Either build the package locally by committing your changes and running:

```bash
$ git archive -o image-display-control.zip HEAD
```

or push your branch up to GitHub and download it from
`https://github.com/<my-fork>/image-display-control-wordpress/archive/refs/heads/<my-branch>.zip`,
e.g.
https://github.com/Frameright/image-display-control-wordpress/archive/refs/heads/master.zip
.
