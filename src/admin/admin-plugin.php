<?php
/**
 * Imported only when browsing admin panel.
 *
 * @package Frameright\Admin
 */

namespace Frameright\Admin;

require_once __DIR__ . '/filesystem.php';
require_once __DIR__ . '/xmp.php';

require_once __DIR__ . '/../debug.php';
use Frameright\Debug;
require_once __DIR__ . '/../global-functions.php';
use Frameright\GlobalFunctions;

/**
 * Implementation of the plugin when inside the admin panel.
 */
class AdminPlugin {
    /**
     * Constructor.
     *
     * @param Mock_stdClass $global_functions_mock Mock of GlobalFunctions if
     *                                             running tests.
     * @param Mock_stdClass $filesystem_mock Mock of Filesystem if running
     *                                       tests.
     * @param Mock_stdClass $xmp_mock Mock of Xmp if running tests.
     */
    public function __construct(
        $global_functions_mock = null,
        $filesystem_mock = null,
        $xmp_mock = null
    ) {
        $this->global_functions = $global_functions_mock
            ? $global_functions_mock
            : new GlobalFunctions();
        $this->filesystem = $filesystem_mock
            ? $filesystem_mock
            : new Filesystem($this->global_functions);
        $this->xmp = $xmp_mock ? $xmp_mock : new Xmp();

        $this->global_functions->add_filter('wp_handle_upload', [
            $this,
            'handle_image_upload',
        ]);

        $this->global_functions->add_filter(
            'wp_read_image_metadata',
            [$this, 'populate_image_metadata'],
            10, // default priority
            5 // number of arguments
        );
    }

    /**
     * Filter called when an image gets uploaded to the media library.
     *
     * @param array $data Filter input/output with the following keys:
     *                    * 'file': '/absolute/path/to/img.jpg'
     *                    * 'url': 'https://mywordpress.com/wp-content/uploads/2022/10/img.jpg'
     *                    * 'type': 'image/jpeg'.
     * @return array Unmodified filter input/output.
     */
    public function handle_image_upload($data) {
        Debug\log('An image got uploaded: ' . print_r($data, true));
        $this->create_hardcrops($data['file']);
        return $data;
    }

    /**
     * Filter called when the IPTC/EXIF/XMP metadata of an image needs to be
     * read.
     *
     * @param array  $meta Filter input/output already populated by
     *                     wp_read_image_metadata() using iptcparse() and
     *                     exif_read_data().
     * @param string $file Absolute path to the image.
     * @param int    $image_type Type of image, one of the `IMAGETYPE_XXX`
     *                           constants.
     * @param array  $iptc Output of `iptcparse($info['APP13'])`.
     * @param array  $exif Output of `exif_read_data($file)`.
     * @return array Filter input/output extended with relevant XMP Image
     *                      Region metadata. See
     *                      https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#image-region
     */
    public function populate_image_metadata(
        $meta,
        $file,
        $image_type,
        $iptc,
        $exif
    ) {
        Debug\log("Populating WordPress metadata for $file ...");

        if (array_key_exists('image_regions', $meta)) {
            Debug\log('Already populated.');
        } else {
            // FIXME do not to that for generated hardcrops, only for the
            // original image. Unfortunately $iptc, $exif and $meta can't be
            // used to differenciate the original image and the hardcrops as
            // they are identical.
            $meta['image_regions'] = $this->read_rectangle_cropping_metadata(
                $file
            );
        }

        Debug\log('Resulting metadata: ' . print_r($meta, true));
        return $meta;
    }

    /**
     * Create hardcropped versions of a given source image.
     *
     * @param string $source_image_path Absolute path to the source image.
     */
    private function create_hardcrops($source_image_path) {
        Debug\log("Creating hardcrops of $source_image_path ...");

        $image_regions = $this->read_rectangle_cropping_metadata(
            $source_image_path
        );
        Debug\log(
            'Found ' . count($image_regions) . ' rectangle cropping region(s)'
        );
        foreach ($image_regions as $image_region) {
            $this->create_hardcrop($source_image_path, $image_region);
        }
    }

    /**
     * Create hardcropped version of a given source image.
     *
     * @param string $source_image_path Absolute path to the source image.
     * @param array  $image_region Cropping details.
     */
    private function create_hardcrop($source_image_path, $image_region) {
        // Object for making changes to an image and saving these changes
        // somewhere else:
        $image_editor = $this->global_functions->wp_get_image_editor(
            $source_image_path
        );
        Debug\assert_(
            !$this->global_functions->is_wp_error($image_editor),
            'Could not create image editor'
        );

        $source_image_size = $image_editor->get_size();
        $absolute_image_region = $this->absolute(
            $image_region,
            $source_image_size['width'],
            $source_image_size['height']
        );
        $crop_result = $image_editor->crop(
            $absolute_image_region['x'],
            $absolute_image_region['y'],
            $absolute_image_region['width'],
            $absolute_image_region['height']
        );
        Debug\assert_(
            !$this->global_functions->is_wp_error($crop_result),
            'Could not crop image'
        );

        $target_basename_suffix = '-frameright';
        if ($image_region['id']) {
            $target_basename_suffix .= '-' . $image_region['id'];
        }
        $target_image_file = $this->filesystem->unique_target_file(
            $source_image_path,
            $target_basename_suffix
        );
        Debug\log('Saving to: ' . print_r($target_image_file, true));
        $saved_file = $image_editor->save($target_image_file['path']);
        Debug\assert_(
            !$this->global_functions->is_wp_error($saved_file),
            'Could not save file'
        );
        Debug\log('Saved to: ' . print_r($saved_file, true));
        Debug\assert_(
            $target_image_file['path'] === $saved_file['path'],
            $target_image_file['path'] . ' !== ' . $saved_file['path']
        );
        Debug\assert_(
            $target_image_file['basename'] === $saved_file['file'],
            $target_image_file['basename'] . ' !== ' . $saved_file['file']
        );

        $target_image_title =
            '[frameright:hardcrop] ' .
            $this->filesystem->image_title($source_image_path);
        if ($image_region['id']) {
            $target_image_title .= ' - ' . $image_region['id'];
        }

        $target_attachment_id = $this->global_functions->wp_insert_attachment(
            [
                'post_mime_type' => $saved_file['mime-type'],
                'post_title' => $target_image_title,
            ],
            $saved_file['path'],
            0, // no parent post
            true // report errors
        );
        Debug\assert_(
            !$this->global_functions->is_wp_error($target_attachment_id),
            'Could not insert attachment'
        );

        $source_basename = basename($source_image_path);
        // TODO necessary?
        $attachment_meta_to_be_set = [
            // Mark the attachment as created/owned by us:
            'frameright' => true,
        ];
        foreach ($attachment_meta_to_be_set as $key => $value) {
            $meta_id = $this->global_functions->add_post_meta(
                $target_attachment_id,
                $key,
                $value,
                true // unique key
            );
            Debug\assert_(
                false !== $meta_id,
                "Could not add attachment meta ($key => $value)"
            );
        }

        /** This will:
         *   * create myimage-frameright-scaled.jpg
         *   * create myimage-frameright-1980x1219.jpg for every container size
         *     defined in the current WordPress template
         *   * create a special `_wp_attachment_metadata` attachment meta
         *     containing:
         *       * info about all the generated scaled images
         *       * some of the metadata extracted from the original image
         */
        // TODO necessary?
        $attachment_metadata = $this->global_functions->wp_generate_attachment_metadata(
            $target_attachment_id,
            $saved_file['path']
        );
        Debug\log(
            'Generated WordPress metadata for attached image: ' .
                print_r($attachment_metadata, true)
        );
    }

    /**
     * Reads the rectangle cropping XMP Image Region metadata from a given
     * file. See
     * https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#image-region
     *
     * @param string $path Absolute path to the image.
     * @return array XMP Image Region metadata structured in a way that can
     *               directly be used as WordPress metadata.
     */
    private function read_rectangle_cropping_metadata($path) {
        $wordpress_metadata = [];

        $regions = $this->xmp->read_rectangle_cropping_metadata($path);
        Debug\log('Found relevant image regions: ' . print_r($regions, true));

        foreach ($regions as $region) {
            $wordpress_metadata_region = [
                'id' => $region->id,
                'names' => $region->names,
                'shape' => $region->rbShape,

                // Otherwise relative, see
                // https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#boundary-measuring-unit
                'absolute' => strtolower($region->rbUnit) === 'pixel',

                'x' => $region->rbXY->rbX,
                'y' => $region->rbXY->rbY,
                'height' => $region->rbH,
                'width' => $region->rbW,
            ];
            array_push($wordpress_metadata, $wordpress_metadata_region);
        }

        return $wordpress_metadata;
    }

    /**
     * Make sure the coordinate of an Image Region are absolute and not
     * relative to the source image.
     *
     * @param array $wordpress_metadata_region Output of
     *                                         read_rectangle_cropping_metadata().
     * @param int   $source_image_width Conversion factor in pixels.
     * @param int   $source_image_height Conversion factor in pixels.
     * @return array Modified $wordpress_metadata_region .
     */
    private function absolute(
        $wordpress_metadata_region,
        $source_image_width,
        $source_image_height
    ) {
        if (!$wordpress_metadata_region['absolute']) {
            $wordpress_metadata_region['x'] = (int) round(
                $wordpress_metadata_region['x'] * $source_image_width
            );
            $wordpress_metadata_region['width'] = (int) round(
                $wordpress_metadata_region['width'] * $source_image_width
            );
            $wordpress_metadata_region['y'] = (int) round(
                $wordpress_metadata_region['y'] * $source_image_height
            );
            $wordpress_metadata_region['height'] = (int) round(
                $wordpress_metadata_region['height'] * $source_image_height
            );
            $wordpress_metadata_region['absolute'] = true;
        }

        Debug\assert_(
            $wordpress_metadata_region['x'] +
                $wordpress_metadata_region['width'] <=
                $source_image_width,
            'Cropping width overflow'
        );
        Debug\assert_(
            $wordpress_metadata_region['y'] +
                $wordpress_metadata_region['height'] <=
                $source_image_width,
            'Cropping height overflow'
        );

        return $wordpress_metadata_region;
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;

    /**
     * Collection of file-related helper functions.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Collection of XMP-related helper functions.
     *
     * @var Xmp
     */
    private $xmp;
}
