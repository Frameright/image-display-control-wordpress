[<img src="https://avatars.githubusercontent.com/u/35964478?s=200&v=4" align="left" width="64" height="64">](https://frameright.io)

# Image Display Control WordPress Plugin

An easy way to leverage image cropping metadata on your site. Made with :heart:
by [Frameright](https://frameright.io). Power to the pictures!

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
in XMP. _Image Regions_ are useful for describing specific areas of the image
(e.g. objects, people) or for indicating how the image should be cropped or
rotated to best fit a given container. The
[Frameright app](https://frameright.app/) can be used to define such Image
Regions and insert them in the metadata of a picture.

This WordPress plugin reads such Image Regions in order to automatically crop
images the best possible way, depending on which container they are being
displayed in.

## How does it work?

When uploading an image via the
[Image Library](https://wordpress.org/support/article/using-images/), cropped
versions of that image (so-called _hardcrops_) are automatically generated
according to the Image Region metadata and also added to the Image Library.

Within a post or page an author can then either directly insert these hardcrops
or insert the original image. Upon changing the ratio of the original image
within a post or page, the best suited hardcrop will automatically be rendered
to visitors.

&emsp; :airplane: [Usage](docs/usage.md)

## Contributing

Run the unit tests with:

```bash
$ composer install
$ composer test
```

&emsp; :wrench: [Contributing](docs/contributing.md)

## Dependency tree / credits

- [Frameright/image](https://github.com/Frameright/image), a fork of
  [dchesterton/image](https://github.com/dchesterton/image), an image metadata
  library. Many thanks to [dchesterton](https://github.com/dchesterton)!
  - [`php-xml`](https://www.php.net/manual/en/book.dom.php)
- PHP 5.6+
- WordPress 5.1+
