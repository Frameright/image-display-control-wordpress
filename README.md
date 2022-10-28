[<img src="https://avatars.githubusercontent.com/u/35964478?s=200&v=4" align="left" width="64" height="64">](https://frameright.io)

# Frameright WordPress Plugin

An easy way to leverage image cropping metadata on your site. Power to the
pictures!

## Which image cropping metadata?

An image file (e.g. JPEG, PNG) contains metadata, i.e. information about the
image, e.g. which camera model was used or when the picture has been taken.
This information is usually stored at the beginning of the file. Three main
formats, or bags of metadata, can coexist in a file and the information they
contain partly overlap:

- [IPTC IIM](https://en.wikipedia.org/wiki/IPTC_Information_Interchange_Model),
  developed in the early 1990s;
- [Exif](https://en.wikipedia.org/wiki/Exif), late 1990s;
- [XMP](https://en.wikipedia.org/wiki/Extensible_Metadata_Platform), late 2000s.

The IPTC IIM format (often just called IPTC format) and the Exif format
represent sets of key-value pairs, whereas the newer XMP format is an XML
representation of a more complex
[RDF](https://en.wikipedia.org/wiki/Resource_Description_Framework) graph. The
[XMP Specification Part 3](https://developer.adobe.com/xmp/docs/XMPSpecifications/)
specifies how the XMP metadata are to be serialized and stored in each image
file format (e.g. JPEG, PNG).

The
[IPTC council](https://en.wikipedia.org/wiki/International_Press_Telecommunications_Council)
has defined a standard for storing
[Image Regions](https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#image-region)
in XMP. *Image Regions* are useful for describing specific areas of the image
(e.g. objects, people) or for indicating how the image should be cropped or
rotated to best fit a given container. The
[Frameright app](https://frameright.app/) can be used to define such Image
Regions and insert them in the metadata of a picture.

This WordPress plugin reads such Image Regions in order to automatically crop
images the best possible way, dependending on which container they are being
displayed in.

## Contributing

### Running the unit tests

#### Setting up PHPUnit

Install [PHPUnit](https://phpunit.readthedocs.io/en/9.5/installation.html)
with:

```bash
$ sudo apt install composer
$ composer install
```

#### Running PHPUnit

Run the unit tests with:

```bash
$ composer test
```

### Formatting the code

Pull and run [prettier](https://github.com/prettier/plugin-php) with:

```bash
$ yarn install     # pull
$ composer format  # run
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
Using config file: /.../frameright-wordpress-plugin/wpcs/vendor/squizlabs/php_codesniffer/CodeSniffer.conf
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
$ composer lint
```
