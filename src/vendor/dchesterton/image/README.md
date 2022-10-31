Image metadata library (PHP 5.5+)
=========

> **NOTE**: this fork of [dchesterton/image](https://github.com/dchesterton/image)
> adds the following changes:
>
> * added `Xmp::getImageRegions()`,
> * unit tests made runnable on PHP8,
> * added linter to validate code against any version of PHP >= 5.5 .
>
> Run the linter and the unit tests with: (see also
> https://github.com/Frameright/image/issues/4 )
>
> ```bash
> $ composer install
> $ composer lint
> $ composer test
> ```
>
> Pull the library in your project  via [Composer](https://getcomposer.org/)
> with the following `composer.json`:
>
> ```json
> {
>   "minimum-stability": "dev",
>   "repositories": [
>     {
>       "type": "vcs",
>       "url": "https://github.com/Frameright/image.git"
>     }
>   ],
>   "require": {
>     "dchesterton/image": "dev-master"
>   }
> }
> ```

## Warning: This library is pre-alpha and much of it is a WIP or simply not working at all. Proceed at your own risk.

Supported image types:
   - JPEG
   - PNG
   - ~~GIF~~
   - ~~PDF~~
   - ~~SVG~~
   - ~~WEBP~~
   - ~~TIFF~~
   - ~~DNG~~
   - ~~RAW FORMATS~~
   - ~~CR2, NEF, etc.~~

Supported image meta types:
   - XMP
   - IPTC
   - ~~EXIF~~

### Get metadata

```php
$image = Image::fromFile($filename);

$headline = $image->getXmp()->getHeadline();
$camera = $image->getExif()->getCamera();
...
```

### Modify existing metadata

```php
$image = Image::fromFile($filename);

$xmp = $image->getXmp();
$xmp->setHeadline('A test headline');
$xmp->setCaption('Caption');

$image->getIptc()->setCategory('Category');

$image->save();
```

### Standalone XMP

#### Generating standalone XMP

```php
$xmp = new Xmp;
$xmp->setHeadline('A headline')
...

$data = $xmp->getXml();
```

#### Modifying standalone XMP

```php
$xmp = new Xmp($data); // or Xmp::fromFile($filename)
$xmp->setHeadline('A headline');

$data = $xmp->getXml();
```

### Setting/replacing XMP in image

```php
$xmp = new Xmp;
$xmp->setHeadline('A headline');
...

$image = Image::fromFile($filename);
$image->setXmp($xmp);

$image->save() // or $image->getBytes()
```

### Loading specific image type

When file type is known, you can load the file type directly using the file types' `fromFile` method.

```php
$jpeg = JPEG::fromFile('image.jpg');
$png = PNG::fromFile('image.png');
```

### Instantiate from bytes

If you don't have a file to work with but you do have the image stored in a string (from database, ImageMagick etc.) you can easily instantiate an object from the string.

```php
$data = ...

$jpeg = JPEG::fromString($data);
$jpeg->getXmp()->setHeadline('Test headline');

$jpeg->save('out.jpg'); // or $jpeg->getBytes();
```

### Instantiate from GD or a stream

You can also create an object from a GD resource or a stream.

```php
$gd = imagecreate(100, 100);
$jpeg = JPEG::fromResource($gd);
```

```php
$stream = fopen('...', 'r+');
$jpeg = JPEG::fromStream($stream);
```

### Aggregate metadata

When just want a piece of metadata and don't care whether it's from XMP, IPTC or EXIF, you can use the aggregate meta object.

```php
$image = Image::fromFile($filename);
$headline = $image->getAggregate()->getHeadline();
```

By default it checks XMP first, then IPTC, then EXIF but you can change the priority:

```php
$aggregate = $image->getAggregate();
$aggregate->setPriority(['exif', 'iptc', 'xmp']);

$aggregate->getHeadline(); // will now check EXIF first, then IPTC, then XMP
```

You can also exclude a metadata type if you do not want to use it:

```php
$aggregate->setPriority(['iptc', 'xmp']);
$aggregate->getHeadline(); // will only check IPTC and XMP
```

You can also modify metadata on an aggregate level:

```php
$image = Image::fromFile($filename);
$image->getAggregate()->setHeadline('Headline');

$image->save();
```

This would set the headline in both XMP and IPTC. For maximum compatibility with other software it's recommended to use the aggregate metadata object where available.

#### Get GPS data

```php
$image = ...
$gps = $image->getAggregateMeta()->getGPS(); // checks EXIF and XMP
// or $gps = $image->getExif()->getGPS();

$lat = $gps->getLatitude();
```
