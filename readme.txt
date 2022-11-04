=== Image Display Control ===
Contributors: lourot
Tags: image, crop, cropping, image crop, image quality, image display control, frameright
Requires at least: 5.1
Tested up to: 6.0
Requires PHP: 5.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Stable Tag: 0.0.3

An easy way to leverage image cropping metadata on your site. Made by Frameright. Power to the pictures!

== Description ==

An image file (e.g. JPEG, PNG) contains metadata, i.e. information about the
image, e.g. which camera model was used, when the picture has been taken or
various image regions. Image regions are useful for describing specific areas
of the image (e.g. objects, people) or for indicating how the image should be
cropped or rotated to best fit a given container.

This WordPress plugin reads such image regions in order to automatically crop
images the best possible way, depending on which container they are being
displayed in.

The [Frameright app](https://frameright.app/) can be used to define such image
regions and insert them in the metadata of a picture.

= How does it work? =

When uploading an image via the Image Library, cropped versions of that image
(so-called hardcrops) are automatically generated according to the image region
metadata and also added to the Image Library.

Within a post or page an author can then either directly insert these hardcrops
or insert the original image. Upon changing the ratio of the original image
within a post or page, the best suited hardcrop will automatically be rendered
to visitors.

== Frequently Asked Questions ==

= I have an issue or I want to contribute code =

Please use the [GitHub repository](https://github.com/Frameright/image-display-control-wordpress)
to raise [issues](https://github.com/Frameright/image-display-control-wordpress/issues)
about the plugin or submit pull requests.

== Changelog ==

= 0.0.3 (2022-11-03) =
* Renamed software components to avoid name clashes with other plugins.

= 0.0.2 (2022-10-31) =
* Improved algorithm for automatically selecting the best hardcrop.

= 0.0.1 (2022-10-27) =
* Initial Release.

== Credits ==

This plugin is based on [Frameright/image](https://github.com/Frameright/image),
a fork of [dchesterton/image](https://github.com/dchesterton/image), an image
metadata library. Many thanks to [dchesterton](https://github.com/dchesterton)!
