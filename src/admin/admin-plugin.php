<?php
/**
 * Implementation of the plugin part firing on administrative hooks.
 *
 * @package FramerightImageDisplayControl\Admin
 */

namespace FramerightImageDisplayControl\Admin;

require_once __DIR__ . '/filesystem.php';
require_once __DIR__ . '/xmp.php';

require_once __DIR__ . '/../debug.php';
use FramerightImageDisplayControl\Debug;
require_once __DIR__ . '/../global-functions.php';
use FramerightImageDisplayControl\GlobalFunctions;

/**
 * Implementation of the plugin part firing on administrative hooks.
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

        $this->pending_attachment_meta_to_be_set = [];

        $this->global_functions->add_filter('wp_handle_upload', [
            $this,
            'handle_file_upload',
        ]);

        $this->global_functions->add_action('add_attachment', [
            $this,
            'set_attachment_meta',
        ]);
    }

    /**
     * Filter called when a file gets uploaded, e.g. when an image is uploaded
     * to the media library.
     *
     * @param array $data Filter input/output with the following keys:
     *                    * 'file': '/absolute/path/to/img.jpg'
     *                    * 'url': 'https://mywordpress.com/wp-content/uploads/2022/10/img.jpg'
     *                    * 'type': 'image/jpeg'.
     * @return array Unmodified filter input/output.
     */
    public function handle_file_upload($data) {
        Debug\log('A file got uploaded: ' . print_r($data, true));
        if (0 === strpos($data['type'], 'image/')) {
            $this->create_hardcrops($data['file'], $data['url']);
        }
        return $data;
    }

    /**
     * Action called once an attachment has been added. If any attachment meta
     * for this new attachment is found in
     * $this->pending_attachment_meta_to_be_set , set it.
     *
     * @param int $post_ID Attachment ID.
     */
    public function set_attachment_meta($post_ID) {
        Debug\log("Attachment $post_ID has just been added");
        $attachment_url = $this->global_functions->wp_get_attachment_url(
            $post_ID
        );
        Debug\assert_(
            $attachment_url,
            "Could not determine URL of attachment $post_ID"
        );

        if (
            array_key_exists(
                $attachment_url,
                $this->pending_attachment_meta_to_be_set
            )
        ) {
            $attachment_meta_to_be_set =
                $this->pending_attachment_meta_to_be_set[$attachment_url];
            Debug\log(
                'Attachment meta to be set: ' .
                    print_r($attachment_meta_to_be_set, true)
            );

            foreach ($attachment_meta_to_be_set as $key => $value) {
                $meta_id = $this->global_functions->add_post_meta(
                    $post_ID,
                    $key,
                    $value,
                    true // unique key
                );
                Debug\assert_(
                    false !== $meta_id,
                    "Could not add attachment meta ($key => " .
                        print_r($value, true)
                );
            }

            unset($this->pending_attachment_meta_to_be_set[$attachment_url]);
        } else {
            Debug\log('No pending attachment meta found.');
        }
    }

    /**
     * Create hardcropped versions of a given source image.
     *
     * @param string $source_image_path Absolute path to the source image.
     * @param string $source_image_url URL of the source image.
     */
    private function create_hardcrops($source_image_path, $source_image_url) {
        Debug\log("Creating hardcrops of $source_image_path ...");

        $image_regions = $this->read_rectangle_cropping_metadata(
            $source_image_path
        );
        Debug\log(
            'Found ' . count($image_regions) . ' rectangle cropping region(s)'
        );

        if (!count($image_regions)) {
            return;
        }

        $hardcrop_attachment_ids = [];
        foreach ($image_regions as $image_region) {
            array_push(
                $hardcrop_attachment_ids,
                $this->create_hardcrop($source_image_path, $image_region)
            );
        }

        // The goal here is to set as attachment meta on the original image the
        // list of created hardcrops. However this is not possible yet in this
        // hook to set attachment meta. So we store it for a later hook.
        $this->pending_attachment_meta_to_be_set[$source_image_url] = [
            'frameright_has_hardcrops' => $hardcrop_attachment_ids,
            'frameright_has_image_regions' => $image_regions,
        ];
    }

    /**
     * Create hardcropped version of a given source image and register it in
     * WordPress.
     *
     * @param string $source_image_path Absolute path to the source image.
     * @param array  $image_region Cropping details.
     * @return int Created WordPress attachment ID.
     */
    private function create_hardcrop($source_image_path, $image_region) {
        $saved_file = $this->create_hardcrop_on_disk(
            $source_image_path,
            $image_region
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

        /** This will:
         *   * create myimage-frameright-scaled.jpg
         *   * create myimage-frameright-1980x1219.jpg for every container size
         *     defined in the current WordPress template
         *   * create a special `_wp_attachment_metadata` attachment meta
         *     containing:
         *       * info about all the generated scaled images
         *       * some of the metadata extracted from the original image
         */
        $attachment_metadata = $this->global_functions->wp_generate_attachment_metadata(
            $target_attachment_id,
            $saved_file['path']
        );
        Debug\log(
            'Generated WordPress metadata for attached image: ' .
                print_r($attachment_metadata, true)
        );

        return $target_attachment_id;
    }

    /**
     * Create hardcropped version of a given source image and store it in a new
     * file on disk.
     *
     * @param string $source_image_path Absolute path to the source image.
     * @param array  $image_region Cropping details.
     * @return array Output of https://developer.wordpress.org/reference/classes/wp_image_editor/save/
     */
    private function create_hardcrop_on_disk(
        $source_image_path,
        $image_region
    ) {
        // Object for making changes to an image and saving these changes
        // somewhere else:
        $image_editor = $this->global_functions->wp_get_image_editor(
            $source_image_path
        );
        Debug\assert_(
            !$this->global_functions->is_wp_error($image_editor),
            'Could not create image editor for cropping'
        );

        $absolute_image_region = $this->absolute($image_region);
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

        return $saved_file;
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
        $image_editor = $this->global_functions->wp_get_image_editor($path);
        Debug\assert_(
            !$this->global_functions->is_wp_error($image_editor),
            'Could not create image editor for reading image size'
        );
        $image_size = $image_editor->get_size();

        $wordpress_metadata = [];

        $regions = $this->xmp->read_rectangle_cropping_metadata($path);
        Debug\log('Found relevant image regions: ' . print_r($regions, true));

        foreach ($regions as $region) {
            $wordpress_metadata_region = [
                'id' => $region->id,
                'names' => $region->names,
                'shape' => $region->rbShape,

                // Can be 'relative' or 'pixel', see
                // https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#boundary-measuring-unit
                'unit' => $region->rbUnit,

                // Useful when unit is 'pixel', see
                // https://github.com/Frameright/image-display-control-web-component/blob/main/image-display-control/docs/reference/attributes.md
                'imageWidth' => $image_size['width'],
                'imageHeight' => $image_size['height'],

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
     * @return array Modified $wordpress_metadata_region .
     */
    private function absolute($wordpress_metadata_region) {
        $unit = strtolower($wordpress_metadata_region['unit']);
        if ('pixel' !== $unit) {
            Debug\assert_(
                'relative' === $unit,
                'Unknown region unit: ' . $unit
            );

            $wordpress_metadata_region['x'] = (int) round(
                $wordpress_metadata_region['x'] *
                    $wordpress_metadata_region['imageWidth']
            );
            $wordpress_metadata_region['width'] = (int) round(
                $wordpress_metadata_region['width'] *
                    $wordpress_metadata_region['imageWidth']
            );
            $wordpress_metadata_region['y'] = (int) round(
                $wordpress_metadata_region['y'] *
                    $wordpress_metadata_region['imageHeight']
            );
            $wordpress_metadata_region['height'] = (int) round(
                $wordpress_metadata_region['height'] *
                    $wordpress_metadata_region['imageHeight']
            );
            $wordpress_metadata_region['unit'] = 'pixel';
        }

        Debug\assert_(
            $wordpress_metadata_region['x'] +
                $wordpress_metadata_region['width'] <=
                $wordpress_metadata_region['imageWidth'],
            'Cropping width overflow'
        );
        Debug\assert_(
            $wordpress_metadata_region['y'] +
                $wordpress_metadata_region['height'] <=
                $wordpress_metadata_region['imageHeight'],
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

    /**
     * Array of attachment meta to be set in a later hook. Looks like:
     *
     *   [
     *     'https://mywordpress.dev/wp-content/uploads/2022/10/img.jpg' => [
     *       'frameright_has_hardcrops => [43, 44],
     *     ],
     *   ]
     *
     * @var array
     */
    private $pending_attachment_meta_to_be_set;
}
