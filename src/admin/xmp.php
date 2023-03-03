<?php
/**
 * XMP-related helper functions.
 *
 * @package FramerightImageDisplayControl\Admin
 */

namespace FramerightImageDisplayControl\Admin;

require_once __DIR__ . '/../debug.php';
use FramerightImageDisplayControl\Debug;

/**
 * Note: the native iptcparse() and exif_read_data() PHP functions are of no
 * help here because they both don't parse the XMP metadata (the metadata in
 * XML/RDF format). Instead they only parse the binary metadata present earlier
 * in the image file.
 *
 * Other native PHP functions which aren't helpful here:
 *   * Imagick::getImageProperties() returns a few date-related XMP items but
 *     not the ones we are interested in.
 *   * Imagick::identifyImage() for the same reasons.
 *
 * Thus we have to use a third-party library in order to parse the XMP Image
 * Region metadata we are interested in. Loading it here.
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Collection of XMP-related helper functions. This class has conceptually no
 * state, so all methods can be understood as static.
 */
class Xmp {
    /**
     * Reads the rectangle cropping XMP Image Region metadata from a given
     * file. See
     * https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#image-region
     *
     * @param string $path Absolute path to the image.
     * @return array XMP Image Region metadata.
     */
    public function read_rectangle_cropping_metadata($path) {
        $result = [];

        try {
            // FIXME: the library relies on the file extension to determine the
            // file format. But in case there is no extension, WordPress still
            // knows the format and could pass the information here. See
            // https://github.com/Frameright/php-image-metadata-parser/issues/5
            $xmp_metadata = \CSD\Image\Image::fromFile($path)->getXmp();

            $result = $xmp_metadata->getImageRegions(
                \CSD\Image\Metadata\Xmp\ShapeFilter::RECTANGLE,
                \CSD\Image\Metadata\Xmp\RoleFilter::CROP
            );
        } catch (\Exception $e) {
            Debug\log($e->getMessage());
            Debug\log($e->getTraceAsString());
        }

        return $result;
    }
}
