<?php
/**
 * Implementation of the plugin part firing on administrative hooks.
 *
 * @package FramerightImageDisplayControl\Admin
 */

namespace FramerightImageDisplayControl\Admin;

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
     * @param Mock_stdClass $xmp_mock Mock of Xmp if running tests.
     */
    public function __construct(
        $global_functions_mock = null,
        $xmp_mock = null
    ) {
        $this->global_functions = $global_functions_mock
            ? $global_functions_mock
            : new GlobalFunctions();
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
            $this->parse_image_regions($data['file'], $data['url']);
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
     * Parse image regions from the XMP metadata of a given image and populate
     * pending_attachment_meta_to_be_set out of it, for a later hook to set it
     * as attachment meta.
     *
     * @param string $source_image_path Absolute path to the source image.
     * @param string $source_image_url URL of the source image.
     */
    private function parse_image_regions(
        $source_image_path,
        $source_image_url
    ) {
        Debug\log("Parsing image regions of $source_image_path ...");

        $image_regions = $this->xmp->read_rectangle_cropping_metadata(
            $source_image_path
        );

        // LA_TEMP
        if (false !== strpos($source_image_path, '/skater.')) {
            array_push($image_regions, [
                'id' => 'horizontalbanner',
                'names' => ['Horizontal banner'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 5760,
                'imageHeight' => 3840,
                'x' => 2343,
                'y' => 858,
                'width' => 3417,
                'height' => 1281,
            ]);
            array_push($image_regions, [
                'id' => 'square',
                'names' => ['Square'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 5760,
                'imageHeight' => 3840,
                'x' => 2462,
                'y' => 1097,
                'width' => 782,
                'height' => 782,
            ]);
            array_push($image_regions, [
                'id' => 'tallportrait',
                'names' => ['Tall portrait'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 5760,
                'imageHeight' => 3840,
                'x' => 2345,
                'y' => 850,
                'width' => 1122,
                'height' => 2990,
            ]);
        }
        if (false !== strpos($source_image_path, '/car.')) {
            array_push($image_regions, [
                'id' => 'horizontalbanner',
                'names' => ['Horizontal banner'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 4933,
                'imageHeight' => 3289,
                'x' => 520,
                'y' => 1026,
                'width' => 3695,
                'height' => 1385,
            ]);
            array_push($image_regions, [
                'id' => 'square',
                'names' => ['Square'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 4933,
                'imageHeight' => 3289,
                'x' => 804,
                'y' => 807,
                'width' => 1647,
                'height' => 1647,
            ]);
            array_push($image_regions, [
                'id' => 'tallportrait',
                'names' => ['Tall portrait'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 4933,
                'imageHeight' => 3289,
                'x' => 730,
                'y' => 79,
                'width' => 1204,
                'height' => 3210,
            ]);
        }
        if (false !== strpos($source_image_path, '/surfer.')) {
            array_push($image_regions, [
                'id' => 'horizontalbanner',
                'names' => ['Horizontal banner'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 2905,
                'imageHeight' => 1937,
                'x' => 0,
                'y' => 426,
                'width' => 2905,
                'height' => 1089,
            ]);
            array_push($image_regions, [
                'id' => 'square',
                'names' => ['Square'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 2905,
                'imageHeight' => 1937,
                'x' => 795,
                'y' => 0,
                'width' => 1937,
                'height' => 1937,
            ]);
            array_push($image_regions, [
                'id' => 'tallportrait',
                'names' => ['Tall portrait'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 2905,
                'imageHeight' => 1937,
                'x' => 2035,
                'y' => 0,
                'width' => 726,
                'height' => 1937,
            ]);
        }
        if (false !== strpos($source_image_path, '/birds.')) {
            array_push($image_regions, [
                'id' => 'horizontalbanner',
                'names' => ['Horizontal banner'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 5391,
                'imageHeight' => 3633,
                'x' => 553,
                'y' => 2392,
                'width' => 3301,
                'height' => 1238,
            ]);
            array_push($image_regions, [
                'id' => 'square',
                'names' => ['Square'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 5391,
                'imageHeight' => 3633,
                'x' => 668,
                'y' => 915,
                'width' => 2660,
                'height' => 2660,
            ]);
            array_push($image_regions, [
                'id' => 'tallportrait',
                'names' => ['Tall portrait'],
                'shape' => 'rectangle',
                'unit' => 'pixel',
                'imageWidth' => 5391,
                'imageHeight' => 3633,
                'x' => 876,
                'y' => 0,
                'width' => 1362,
                'height' => 3633,
            ]);
        }

        Debug\log(
            'Found ' . count($image_regions) . ' rectangle cropping region(s)'
        );

        if (!count($image_regions)) {
            return;
        }

        // The goal here is to set as attachment meta on the original image the
        // list of found regions. However it is not possible yet in this hook to
        // set attachment meta yet. So we store it for a later hook.
        $this->pending_attachment_meta_to_be_set[$source_image_url] = [
            'frameright_has_image_regions' => $image_regions,
        ];
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;

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
