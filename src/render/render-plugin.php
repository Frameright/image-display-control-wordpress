<?php
/**
 * Implementation of the plugin part firing on rendering hooks.
 *
 * @package FramerightImageDisplayControl\Render
 */

namespace FramerightImageDisplayControl\Render;

require_once __DIR__ . '/../debug.php';
use FramerightImageDisplayControl\Debug;
require_once __DIR__ . '/../global-functions.php';
use FramerightImageDisplayControl\GlobalFunctions;

/**
 * Implementation of the plugin part firing on rendering hooks.
 */
class RenderPlugin {
    /**
     * Constructor.
     *
     * @param Mock_stdClass $global_functions_mock Mock of GlobalFunctions if
     *                                             running tests.
     */
    public function __construct($global_functions_mock = null) {
        $this->global_functions = $global_functions_mock
            ? $global_functions_mock
            : new GlobalFunctions();

        /**
         * When rendering an image, the most important filters involved are
         * the following ones, in this order:
         *   * render_block_data
         *   * wp_img_tag_add_width_and_height_attr
         *   * wp_get_attachment_metadata
         *   * wp_image_src_get_dimensions
         *   * wp_calculate_image_srcset_meta
         *   * wp_calculate_image_srcset
         *   * wp_calculate_image_sizes
         *   * wp_content_img_tag
         *
         * The goal of this class is to replace
         *
         *   <img src='<orig_url>'
         *        srcset="<orig_url> 1024w,
         *                <orig_url> 300w,
         *        [...]
         *
         * with either
         *
         *   <img src='<best_crop_url>'
         *        srcset="<best_crop_url> 1024w,
         *                <best_crop_url> 300w,
         *        [...]
         *
         * or with
         *   <img-frameright
         *        [...]
         *
         * After having played around with all filters mentioned above, it
         * appears that:
         *   * wp_calculate_image_srcset is the best filter for replacing all
         *     srcset attributes.
         *   * wp_content_img_tag is the best filter for replacing the <img>
         *     tag entirely.
         *   * wp_content_img_tag would be the best filter for replacing the
         *     src attribute, however it seems like leaving it unchanged still
         *     provides good results.
         */

        if (self::ENABLE_EXPERIMENTAL_FEATURE_WEB_COMPONENT) {
            $this->global_functions->add_action('wp_enqueue_scripts', [
                $this,
                'serve_and_load_web_component_js',
            ]);

            $this->global_functions->add_filter(
                'wp_content_img_tag',
                [$this, 'replace_img_tag'],
                10, // default priority
                3 // number of arguments
            );
        } else {
            $this->global_functions->add_filter(
                'wp_calculate_image_srcset',
                [$this, 'replace_srcsets'],
                10, // default priority
                5 // number of arguments
            );
        }
    }

    /**
     * Filter called when rendering images, giving the opportunity to the
     * plugin to tweak srcset HTML attributes.
     *
     * If the image being rendered is an original image containing XMP Image
     * Regions, we go through all the corresponding hardcrops and replace the
     * srcset attributes by the best hardcrop.
     *
     * @param array  $sources Filter input/output already populated by
     *                        srcset-related data.
     * @param array  $size_array An array of requested width and height values.
     * @param string $image_src Value for the src attribute of the <img> HTML
     *                          element being rendered.
     * @param array  $image_meta The original image metadata as returned by
     *                           `wp_get_attachment_metadata()`.
     * @param int    $attachment_id Attachment ID of the original image.
     * @return array Filter input/output in which image URLs may have been
     *               replaced by those of a better hardcrop.
     */
    public function replace_srcsets(
        $sources,
        $size_array,
        $image_src,
        $image_meta,
        $attachment_id
    ) {
        Debug\log("Replacing srcsets for attachment $attachment_id");

        $hardcrop_attachment_ids = $this->get_hardcrop_attachment_ids(
            $attachment_id
        );
        if (!$hardcrop_attachment_ids) {
            return $sources;
        }

        $hardcrop_with_closest_ratio = $this->find_best_hardcrop(
            $size_array,
            $image_meta,
            $hardcrop_attachment_ids
        );
        if (!$hardcrop_with_closest_ratio) {
            Debug\log('Original image has the best ratio');
            return $sources;
        }

        $hardcrop_url = $this->get_main_url($hardcrop_with_closest_ratio['id']);

        // Let's create a new set of srcset attributes that point to the best
        // hardcrop.
        $hardcrop_sources = [];

        /**
         * We need one item for each container size declared in the template,
         * otherwise WordPress won't accept it. The goal is to produce an array
         * that looks like
         *
         *   [
         *    '300' => [
         *      'url' => 'https://mywordpress.com/wp-content/uploads/2022/10/img-frameright-region-198x300.jpg',
         *      'descriptor' => 'w',
         *      'value' => 300,
         *    ],
         *    '1024' => [
         *      'url' => 'https://mywordpress.com/wp-content/uploads/2022/10/img-frameright-region.jpg',
         *      'descriptor' => 'w',
         *      'value' => 1024,
         *     ],
         *     [...]
         *   ]
         */
        $container_sizes = $this->global_functions->wp_get_registered_image_subsizes();
        foreach ($container_sizes as $container_size_name => $container_size) {
            if ($container_size['crop']) {
                // Skip containers that aren't going to respect the ratio, e.g.
                // 'thumbnail' which is usually 150x150:
                continue;
            }

            $hardcrop_sources[$container_size['width']] = [
                'url' => $hardcrop_url['main'],
                'descriptor' => 'w',
                'value' => $container_size['width'],
            ];

            if (
                array_key_exists(
                    $container_size_name,
                    $hardcrop_with_closest_ratio['meta']['sizes']
                )
            ) {
                $hardcrop_sources[$container_size['width']]['url'] =
                    $hardcrop_url['base'] .
                    $hardcrop_with_closest_ratio['meta']['sizes'][
                        $container_size_name
                    ]['file'];
            }
        }

        Debug\log('New sources: ' . print_r($hardcrop_sources, true));
        return $hardcrop_sources;
    }

    /**
     * Filter called when rendering images, giving the opportunity to the
     * plugin to tweak the HTML <img> tag.
     *
     * If the image being rendered is an original image containing XMP Image
     * Regions, we will replace the <img> tag with an <img-frameright> tag.
     *
     * @param string $filtered_image Full <img> tag.
     * @param string $context Additional context, like the current filter name
     *                        or the function name from where this was called.
     * @param int    $attachment_id The image attachment ID. May be 0 in case
     *                              the image is not an attachment.
     * @return string Filter input/output in which the <img> tag may have been
     *                replaced by an <img-frameright> tag.
     */
    public function replace_img_tag($filtered_image, $context, $attachment_id) {
        Debug\log("Replacing <img> tag for attachment $attachment_id");
        Debug\log("Original tag: $filtered_image");

        /**
         * At this point $filtered_image looks like
         *
         *     <img width="2000" height="1000"
         *          src="https://mywordpress.com/wp-content/uploads/2022/11/myimage.jpg"
         *          class="attachment-post-thumbnail size-post-thumbnail wp-post-image"
         *          alt="" decoding="async" loading="lazy"
         *          srcset="https://mywordpress.com/wp-content/uploads/2022/11/myimage.jpg 2000w,
         *                  https://mywordpress.com/wp-content/uploads/2022/11/myimage-300x150.jpg 300w,
         *                  https://mywordpress.com/wp-content/uploads/2022/11/myimage-1024x512.jpg 1024w,
         *                  https://mywordpress.com/wp-content/uploads/2022/11/myimage-768x384.jpg 768w,
         *                  https://mywordpress.com/wp-content/uploads/2022/11/myimage-1536x768.jpg 1536w"
         *          sizes="(max-width: 2000px) 100vw, 2000px"
         *     />
         */
        $parsed_img_tag = self::parse_img_tag($filtered_image);
        $document = $parsed_img_tag['document'];
        $img_element = $parsed_img_tag['element'];
        $src_attribute = $parsed_img_tag['src'];

        if (!$attachment_id) {
            /**
             * This happens in two cases:
             *   * The image is outside WordPress, like a gravatar, in which
             *     case we should return early and leave the <img> tag
             *     unmodified.
             *   * The image isn't part of a post/page content, but for example
             *     a featured image. In this case we should try to figure out
             *     if there is an attachment for this image URL, in order to
             *     see if the image has some relevant image regions.
             */
            $attachment_id = $this->global_functions->attachment_url_to_postid(
                $src_attribute
            );
            if (!$attachment_id) {
                Debug\log('Image is not in media library, leaving unchanged');
                return $filtered_image;
            }
        }

        $regions = $this->global_functions->get_post_meta(
            $attachment_id,
            'frameright_has_image_regions',
            true
        );
        if (!$regions) {
            Debug\log('Image has no relevant image regions, leaving unchanged');
            return $filtered_image;
        }
        Debug\log('Found relevant image regions: ' . print_r($regions, true));
        $regions_json = $this->global_functions->wp_json_encode($regions);
        Debug\assert_($regions_json, 'Could not serialize image regions');

        $frameright_tag = self::build_frameright_tag(
            $document,
            $img_element,
            $regions_json
        );
        Debug\log("Resulting tag: $frameright_tag");
        return $frameright_tag;
    }

    /**
     * Deliver the JavaScript code of the <img-frameright> web component to
     * the front-end.
     */
    public function serve_and_load_web_component_js() {
        $relative_path_to_js_assets = '../assets/js/build/';
        $js_script_name = 'img-frameright.js';
        $absolute_path_to_js_script = realpath(
            __DIR__ . '/' . $relative_path_to_js_assets . $js_script_name
        );
        Debug\assert_($absolute_path_to_js_script, 'Could not find js assets');

        $url_to_js_assets = $this->global_functions->plugin_dir_url(
            $absolute_path_to_js_script
        );
        $url_to_js_script = $url_to_js_assets . $js_script_name;

        $this->global_functions->wp_enqueue_script(
            self::JS_SCRIPT_UNIQUE_HANDLE,
            $url_to_js_script,
            [], // deps
            '42.42.0', // dummy version added to URL for cache busting purposes
            true // put just before </body> instead of </head>
        );
    }

    /**
     * If true, instead of modifying the `<img srcset="...` attributes, the
     * plugin will replace the tag with the `<img-frameright ...` web
     * component.
     */
    const ENABLE_EXPERIMENTAL_FEATURE_WEB_COMPONENT = false;

    const JS_SCRIPT_UNIQUE_HANDLE = 'frameright';

    /**
     * If the given image has associated hardcrops, return their WordPress
     * attachment IDs.
     *
     * @param int $attachment_id Attachment ID of the original image.
     * @return array Attachment IDs of associated hardcrops.
     */
    private function get_hardcrop_attachment_ids($attachment_id) {
        $hardcrop_attachment_ids = $this->global_functions->get_post_meta(
            $attachment_id,
            'frameright_has_hardcrops',
            true
        );

        if (!$hardcrop_attachment_ids) {
            $hardcrop_attachment_ids = [];
        }

        Debug\log(
            'Attached hardcrops found: ' .
                print_r($hardcrop_attachment_ids, true)
        );

        return $hardcrop_attachment_ids;
    }

    /**
     * Of all the hardcrops of a given original image, find the one which fits
     * best a given container.
     *
     * @param array $size_array An array of requested width and height values.
     *                          In other words the container size.
     * @param array $image_meta The original image metadata as returned by
     *                          `wp_get_attachment_metadata()`.
     * @param array $hardcrop_attachment_ids Attachment IDs of associated
     *                                       hardcrops.
     * @return array|null Attachment ID and metadata of the best fitting
     *                    hardcrop. Null if the original image fits the
     *                    container best. Supported keys:
     *                    * 'id': attachment ID
     *                    * 'meta': output of wp_get_attachment_metadata()
     */
    private function find_best_hardcrop(
        $size_array,
        $image_meta,
        $hardcrop_attachment_ids
    ) {
        $container_ratio = self::image_ratio($size_array[0], $size_array[1]);
        Debug\log("Container ratio: $container_ratio");

        $original_image_ratio = self::image_ratio(
            $image_meta['width'],
            $image_meta['height']
        );
        Debug\log("Original image ratio: $original_image_ratio");

        $smallest_ratio_diff = self::image_ratio_diff_factor(
            $container_ratio,
            $original_image_ratio
        );
        if ($smallest_ratio_diff < 1.1) {
            Debug\log('Original image has the same ratio as the container');
            return;
        }

        $hardcrop_attachment_id_with_closest_ratio = null;
        foreach ($hardcrop_attachment_ids as $hardcrop_attachment_id) {
            $hardcrop_metadata = $this->global_functions->wp_get_attachment_metadata(
                $hardcrop_attachment_id
            );
            $hardcrop_ratio = self::image_ratio(
                $hardcrop_metadata['width'],
                $hardcrop_metadata['height']
            );
            Debug\log(
                "Hardcrop $hardcrop_attachment_id has ratio " . $hardcrop_ratio
            );
            $ratio_diff = self::image_ratio_diff_factor(
                $container_ratio,
                $hardcrop_ratio
            );
            if ($ratio_diff < $smallest_ratio_diff) {
                $smallest_ratio_diff = $ratio_diff;
                $hardcrop_attachment_id_with_closest_ratio = $hardcrop_attachment_id;
                $hardcrop_metadata_with_closest_ratio = $hardcrop_metadata;
                $hardcrop_closest_ratio = $hardcrop_ratio;
            }
        }

        if (!$hardcrop_attachment_id_with_closest_ratio) {
            Debug\log('Original image is the best fit for this container');
            return;
        }

        Debug\log(
            "Hardcrop $hardcrop_attachment_id_with_closest_ratio has " .
                'the closest ratio. Metadata: ' .
                print_r($hardcrop_metadata_with_closest_ratio, true)
        );

        return [
            'id' => $hardcrop_attachment_id_with_closest_ratio,
            'meta' => $hardcrop_metadata_with_closest_ratio,
        ];
    }

    /**
     * Get the main URL of a given image.
     *
     * An image has several URLs, as it is available in different container
     * sizes ('medium', 'large', etc.). This is the URL to the largest version
     * of this image.
     *
     * @param int $attachment_id Attachment ID of the image.
     * @return array Supported keys:
     *               * 'main': 'https://mywordpress.com/wp-content/uploads/2022/10/img.jpg'
     *               * 'base': 'https://mywordpress.com/wp-content/uploads/2022/10/'
     */
    private function get_main_url($attachment_id) {
        $main_url = $this->global_functions->wp_get_attachment_url(
            $attachment_id
        );
        Debug\assert_($main_url, 'Could not determine URL');

        // This transforms
        // 'https://mywordpress.com/wp-content/uploads/2022/10/img.jpg'
        // into
        // 'https://mywordpress.com/wp-content/uploads/2022/10/'
        $url_base = substr($main_url, 0, strrpos($main_url, '/')) . '/';

        return [
            'main' => $main_url,
            'base' => $url_base,
        ];
    }

    /**
     * Calculates image ratio safely, i.e. by avoiding divisions by 0.
     *
     * @param int $width Image width in pixels.
     * @param int $height Image height in pixels.
     * @return float Image ratio.
     */
    private static function image_ratio($width, $height) {
        return $width / max($height, 1);
    }

    /**
     * Calculates the difference between two image ratios, expressed as the
     * factor >= 1, so that one ratio multiplied by this factor gives the other
     * ratio.
     *
     * @param float $first_ratio First image ratio.
     * @param float $second_ratio Second image ratio.
     * @return float Factor >= 1.
     */
    private static function image_ratio_diff_factor(
        $first_ratio,
        $second_ratio
    ) {
        // Avoid dividing by 0:
        if (!$first_ratio || !$second_ratio) {
            $first_ratio += 0.1;
            $second_ratio += 0.1;
        }

        if ($first_ratio >= $second_ratio) {
            return $first_ratio / $second_ratio;
        }
        return $second_ratio / $first_ratio;
    }

    /**
     * Parse an '<img src="..." />' tag.
     *
     * @param string $img_tag Tag to be parsed.
     * @return array Supported keys:
     *               * 'document':  Instance of \DOMDocument
     *               * 'element':   Instance of \DOMElement
     *               * 'src':       Value (string) of the src= attribute
     */
    private static function parse_img_tag($img_tag) {
        $document = new \DOMDocument();
        $success = $document->loadHTML($img_tag);
        Debug\assert_($success, 'Could not parse ' . $img_tag);

        $elements = $document->getElementsByTagName('img');
        $num_elements = $elements->length;
        Debug\assert_(
            1 === $num_elements,
            "Expected exactly one <img> tag, found $num_elements instead"
        );

        $img_element = $elements->item(0);
        Debug\assert_($img_element, 'Could not find <img> element');

        $src_attribute = $img_element->attributes->getNamedItem('src')
            ->nodeValue;
        Debug\assert_($src_attribute, 'Could not find src= attribute');
        Debug\log("Image URL: $src_attribute");

        return [
            'document' => $document,
            'element' => $img_element,
            'src' => $src_attribute,
        ];
    }

    /**
     * Build an '<img-frameright src="..." />' tag.
     *
     * @param \DOMDocument $document Document containing the original <img>
     *                               element. Will be altered.
     * @param \DOMElement  $img_element Original <img> element.
     * @param string       $regions JSON-serialized image regions.
     * @return string Resulting tag.
     */
    private static function build_frameright_tag(
        $document,
        $img_element,
        $regions
    ) {
        // Create a new <img-frameright> tag and copy all <img> attributes
        // over to it.
        $frameright_element = $document->createElement('img-frameright');
        Debug\assert_(
            $frameright_element,
            'Could not create <img-frameright> element'
        );
        for ($i = 0; $i < $img_element->attributes->length; ++$i) {
            $img_attribute = $img_element->attributes->item($i);
            $frameright_attribute = $frameright_element->setAttribute(
                $img_attribute->name,
                $img_attribute->value
            );
            Debug\assert_(
                $frameright_attribute,
                'Could not create ' . $img_attribute->name . '= attribute'
            );
        }

        // Pass relevant image regions to the web component:
        $frameright_attribute = $frameright_element->setAttribute(
            'image-regions',
            $regions
        );
        Debug\assert_(
            $frameright_attribute,
            'Could not create image-regions= attribute'
        );

        $frameright_tag = $document->saveHTML($frameright_element);
        Debug\assert_($frameright_tag, 'Could not generate <img-frameright>');
        return $frameright_tag;
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
