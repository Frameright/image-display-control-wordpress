# Contributing

## Table of Contents

<!-- toc -->

- [:floppy_disk: Code formatting](#floppy_disk-code-formatting)
- [:memo: Validating](#memo-validating)
  * [Running the unit tests](#running-the-unit-tests)
    + [Setting up PHPUnit](#setting-up-phpunit)
    + [Running PHPUnit](#running-phpunit)
  * [Validating against WordPress coding standards](#validating-against-wordpress-coding-standards)
    + [Setting up PHP_CodeSniffer](#setting-up-php_codesniffer)
    + [Running PHP_CodeSniffer](#running-php_codesniffer)
- [:bookmark_tabs: Documenting](#bookmark_tabs-documenting)
  * [Spellchecking the documentation](#spellchecking-the-documentation)
  * [(Re-)generating tables of contents](#re-generating-tables-of-contents)
- [:gift: Releasing](#gift-releasing)
  * [Version number](#version-number)
  * [Changelog](#changelog)
  * [Last tweaks and checks](#last-tweaks-and-checks)
  * [Git tag](#git-tag)
  * [Build the plugin as a ZIP file](#build-the-plugin-as-a-zip-file)
  * [Set up SVN](#set-up-svn)
  * [Commit to SVN](#commit-to-svn)

<!-- tocstop -->

## :floppy_disk: Code formatting

Pull and run [prettier](https://github.com/prettier/plugin-php) with:

```bash
npm install
npm run format
```

## :memo: Validating

### Running the unit tests

#### Setting up PHPUnit

Install [PHPUnit](https://phpunit.readthedocs.io/en/9.5/installation.html)
with:

```bash
sudo apt install composer
composer install
```

#### Running PHPUnit

Run the unit tests with:

```bash
composer test
```

### Validating against WordPress coding standards

#### Setting up PHP_CodeSniffer

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

#### Running PHP_CodeSniffer

Run PHP_CodeSniffer with:

```bash
composer lint
```

## :bookmark_tabs: Documenting

### Spellchecking the documentation

Pull and run [`mdspell`](https://github.com/lukeapage/node-markdown-spellcheck)
with:

```bash
npm install
npm run spellcheck
```

### (Re-)generating tables of contents

Pull and run [`markdown-toc`](https://github.com/jonschlinkert/markdown-toc)
with:

```bash
npm install
npm run gentoc
```

## :gift: Releasing

### Version number

Choose the next version number according to the rules of
[Semantic Versioning](https://semver.org/) and set it in the following files:

* [frameright.php](../frameright.php#L8)
* [readme.txt](../readme.txt#L9)

> **NOTES**:
>
> * [WordPress Readme Documentation](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
> * [WordPress Readme Example](https://wordpress.org/plugins/readme.txt)
> * [WordPress Readme Validator](https://wordpress.org/plugins/developers/readme-validator/)

### Changelog

Describe the changes made compared to the last released version in the
[changelog](../readme.txt). Browse the git history to make sure nothing has
been left out.

### Last tweaks and checks

Format and validate the source one last time:

```bash
npm run format
npm run gentoc
npm run spellcheck
composer lint
composer test
```

Commit and push any local changes:

```bash
git add -A
git commit -m "<my message>"
git push
```

### Git tag

In the rest of this document we'll assume you are releasing version `1.2.3`.
Create a git tag for the version you are releasing by running:

```bash
git tag 1.2.3
git push --tags
```

### Build the plugin as a ZIP file

Build the package locally by running:

```bash
git archive -o image-display-control.zip HEAD
```

### Set up SVN

WordPress plugins are released to the WordPress Plugin Directory
[using SVN](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/).

Check out the repository:

```bash
cd ../
svn co https://plugins.svn.wordpress.org/image-display-control
cd image-display-control/trunk/
```

### Commit to SVN

Replace the `trunk` files with the previously built archive. This will require
an unpredictable sequence of the following commands:

* `unzip image-display-control.zip`
* `svn update`
* `svn add newfile1 newfile2 newdir1`
* `svn delete oldfile1`
* `svn status`

Commit the `trunk` changes and create a new tag:

```bash
cd ../
svn copy trunk tags/1.2.3
svn commit -m "1.2.3" --username my-wordpress-username --password foo
```

Check the result at https://wordpress.org/plugins/image-display-control/

> **NOTE**: Some things on the WordPress Plugin Directory, like search results,
> may take 72 hours to get updated.
