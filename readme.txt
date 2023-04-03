=== Image Display Control ===
Contributors: lourot, jarsta
Tags: image, crop, cropping, image crop, image quality, image display control, frameright, responsive, design, layout, iptc, metadata
Requires at least: 5.1
Tested up to: 6.2
Requires PHP: 5.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Stable Tag: 0.1.2

An easy way to leverage image region metadata on your site. Made by Frameright. Power to the pictures!

== Description ==

An image file (e.g. JPEG, PNG) contains metadata, i.e. information about the image, e.g. which camera model was used, when the picture has been taken or various image regions. Image regions are useful for describing specific areas of the image (e.g. objects, people) or for indicating how the image should be cropped or rotated to best fit a given container.

This WordPress plugin reads such image regions in order to automatically crop images the best possible way, depending on which container they are being displayed in.

The [Frameright app](https://frameright.app/) can be used to define such image regions and insert them in the metadata of a picture.

= How does it work? =

When rendering a post or a page, the plugin looks for images that have image region metadata and replaces them with a web component automatically zooming on the best suited image region, effectively doing better than a classical middle-crop.

https://www.youtube.com/watch?v=vlyoAPku_NU

== Frequently Asked Questions ==

= I have an issue or I want to contribute code =

Please use the [GitHub repository](https://github.com/Frameright/image-display-control-wordpress) to raise [issues](https://github.com/Frameright/image-display-control-wordpress/issues) about the plugin or submit pull requests.

== Changelog ==

= 0.1.2 (2023-04-03) =
* Marked as validated with WordPress 6.2.

= 0.1.1 (2023-03-07) =
* Added tutorial video.

= 0.1.0 (2023-03-07) =
* Fixed web component sometimes not initializing properly.
* Added support for older browsers. See [here](https://github.com/Frameright/image-display-control-web-component/blob/main/image-display-control/docs/explanation/browsers.md) for more details.
* Improved resolution of images having an `srcset=` HTML attribute when zooming in on a region.
* Now forcing the web component to select a region and zoom in on it, instead of rendering the full original image.
* Fixed crash when uploading non-image files.
* Fixed crash when uploading damaged images.
* Fixed several bugs that were happening with some specific themes, like the plugin doing nothing or the image regions being wrong.
* Stopped generating hardcrops in the media library.

= 0.0.5 (2023-01-26) =
* Switched to rendering a [web component](https://github.com/Frameright/image-display-control-web-component) on the front-end.

= 0.0.4 (2022-11-05) =
* Improved documentation.

= 0.0.3 (2022-11-03) =
* Renamed software components to avoid name clashes with other plugins.

= 0.0.2 (2022-10-31) =
* Improved algorithm for automatically selecting the best hardcrop.

= 0.0.1 (2022-10-27) =
* Initial Release.

== Credits ==

This plugin is based on [Frameright/php-image-metadata-parser](https://github.com/Frameright/php-image-metadata-parser), a fork of [dchesterton/image](https://github.com/dchesterton/image), an image metadata parsing library. Many thanks to [dchesterton](https://github.com/dchesterton)!
