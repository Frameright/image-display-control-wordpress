# Frameright WordPress Plugin

An easy way to leverage image cropping metadata on your site. Power to the
pictures!

## Contributing

### Validating against WordPress coding standards

#### Setting up PHP_CodeSniffer

To validate your changes against the
[WordPress coding standards](https://github.com/WordPress/WordPress-Coding-Standards),
first pull them in to `wpcs/` by running:

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
Using config file: /.../frameright-wordpress-plugin/wpcs/vendor/squizlabs/php_codesniffer/CodeSniffer.conf
Config value "installed_paths" added successfully

$ ./wpcs/vendor/bin/phpcs -i
The installed coding standards are MySource, PEAR, PSR1, PSR2, PSR12, Squiz, Zend, WordPress, WordPress-Core, WordPress-Docs and WordPress-Extra
```

Pull also the
[rulesets for PHP cross-version compatibility](https://github.com/PHPCompatibility/PHPCompatibilityWP)
with:

```bash
$ pushd wpcs/
$ docker run --rm -it --volume $PWD:/app -u `id -u`:`id -g` \
    composer:1.10.19 require --dev phpcompatibility/phpcompatibility-wp:"*"
$ popd
```

#### Running PHP_CodeSniffer

Run PHP_CodeSniffer with:

```bash
$ ./wpcs/vendor/bin/phpcs -s
```
