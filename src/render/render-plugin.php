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
         *   * render_block_core/image
         *   * render_block_core/post-featured-image
         *   * wp_img_tag_add_width_and_height_attr
         *   * wp_get_attachment_metadata
         *   * wp_image_src_get_dimensions
         *   * wp_calculate_image_srcset_meta
         *   * wp_calculate_image_srcset
         *   * wp_calculate_image_sizes
         *   * wp_content_img_tag
         *   * post_thumbnail_html
         *
         * The goal of this class is to replace
         *
         *   <img src="<orig_url>"
         *        srcset="<orig_url> 1024w,
         *                <orig_url> 300w,
         *        [...]
         *
         * with
         *
         *   <img is="image-display-control"
         *        src="<orig_url>"
         *        srcset="<orig_url> 1024w,
         *                <orig_url> 300w,
         *        [...]
         *
         * After having played around with all filters mentioned above, it
         * appears that:
         *   * wp_content_img_tag is the best filter for replacing the <img>
         *     tag entirely.
         *   * Sometimes wp_content_img_tag doesn't get called, for example
         *     when rendering the featured image of a post in some particular
         *     themes. In this case post_thumbnail_html is called instead.
         *   * Some themes define special thumbnail sizes with different ratios,
         *     leading to the creation of hardcrops. On such hardcrops, image
         *     region coordinates are wrong. wp_get_attachment_image_src gets
         *     called when retrieving such a thumbnail. There we get a chance to
         *     make sure we don't return such a hardcrop.
         */

        $this->global_functions->add_action('wp_enqueue_scripts', [
            $this,
            'serve_css',
        ]);
        $this->global_functions->add_action('wp_enqueue_scripts', [
            $this,
            'serve_web_component_js',
        ]);

        $this->global_functions->add_filter(
            'wp_get_attachment_image_src',
            [$this, 'tweak_img_src'],
            10, // default priority
            4 // number of arguments
        );

        $this->global_functions->add_filter(
            'wp_content_img_tag',
            [$this, 'tweak_img_tag'],
            10, // default priority
            3 // number of arguments
        );
        $this->global_functions->add_filter(
            'post_thumbnail_html',
            [$this, 'tweak_thumbnail_html'],
            10, // default priority
            5 // number of arguments
        );
    }

    /**
     * Filter called when fetching an image thumbnail, giving the opportunity
     * to make sure we don't return a hardcrop.
     *
     * See
     * https://developer.wordpress.org/reference/hooks/wp_get_attachment_image_src/
     *
     * @param array|false  $image Array of image data, or boolean false if
     *                            no image is available. Items:
     *                            * (string) Image source URL.
     *                            * (int) Image width in pixels.
     *                            * (int) Image height in pixels.
     *                            * (bool) Whether the image is a resized image.
     * @param int          $attachment_id Image attachment ID.
     * @param string|int[] $size Requested image size.
     * @param bool         $icon Whether the image should be treated as an icon.
     * @return array|false Filter input/output in which the image source URL
     *                     may have been replaced by a non-hardcrop URL.
     */
    public function tweak_img_src($image, $attachment_id, $size, $icon) {
        Debug\log('Maybe swapping image source URL...');
        if (!$image) {
            Debug\log('No URL, skipping.');
            return $image;
        }
        if (!$attachment_id) {
            Debug\log("$image[0] has no attachment ID, skipping.");
            return $image;
        }
        Debug\log("Image source URL: $image[0]");
        Debug\log("Image attachment ID: $attachment_id");

        if ($icon) {
            Debug\log('Image is an icon, skipping.');
            return $image;
        }
        if (!$image[3]) {
            Debug\log('Image is not a resized image, skipping.');
            return $image;
        }

        $regions = $this->global_functions->get_post_meta(
            $attachment_id,
            'frameright_has_image_regions',
            true
        );
        if (!$regions) {
            Debug\log('Image has no regions, skipping.');
            return $image;
        }

        $main_url = $this->global_functions->wp_get_attachment_url(
            $attachment_id
        );
        Debug\assert_(
            $main_url,
            "Could not determine main URL for attachment $attachment_id"
        );
        Debug\log("Swapping for main URL: $main_url");

        $image[0] = $main_url;
        $image[3] = false;
        return $image;
    }

    /**
     * Filter called when rendering images, giving the opportunity to the
     * plugin to tweak the HTML <img> tag.
     *
     * If the image being rendered is an original image containing XMP Image
     * Regions, we will add image-display-control-related attributes to the
     * <img> tag.
     *
     * @param string $filtered_image Full <img> tag.
     * @param string $context Additional context, like the current filter name
     *                        or the function name from where this was called.
     * @param int    $attachment_id The image attachment ID. May be 0 in case
     *                              the image is not an attachment.
     * @return string Filter input/output in which the <img> tag may have
     *                received additional HTML attributes.
     */
    public function tweak_img_tag($filtered_image, $context, $attachment_id) {
        return $this->add_img_subtag_attributes(
            $filtered_image,
            $attachment_id
        );
    }

    /**
     * Filter called when rendering the HTML of a post thumbnail, giving the
     * opportunity to the plugin to tweak the HTML <img> tag.
     *
     * If the image being rendered is an original image containing XMP Image
     * Regions, we will add image-display-control-related attributes to the
     * <img> tag.
     *
     * See https://developer.wordpress.org/reference/hooks/post_thumbnail_html/
     *
     * @param string       $html The post thumbnail HTML snippet.
     * @param int          $post_id The post ID.
     * @param int          $post_thumbnail_id The image attachment ID, or 0 if
     *                                        there isn't one.
     * @param string|int[] $size Requested image size.
     * @param string|array $attr Query string of attributes or array of
     *                           attributes.
     * @return string Filter input/output in which the <img> tag may have
     *                received additional HTML attributes.
     */
    public function tweak_thumbnail_html(
        $html,
        $post_id,
        $post_thumbnail_id,
        $size,
        $attr
    ) {
        return $this->add_img_subtag_attributes($html, $post_thumbnail_id);
    }

    /**
     * Deliver our CSS to the front-end.
     */
    public function serve_css() {
        $relative_path_to_css_assets = '../assets/css/';
        $stylesheet_name = 'frameright.css';
        $absolute_path_to_stylesheet = realpath(
            __DIR__ . '/' . $relative_path_to_css_assets . $stylesheet_name
        );
        Debug\assert_($absolute_path_to_stylesheet, 'Could not find js assets');

        $url_to_css_assets = $this->global_functions->plugin_dir_url(
            $absolute_path_to_stylesheet
        );
        $url_to_stylesheet = $url_to_css_assets . $stylesheet_name;

        $this->global_functions->wp_enqueue_style(
            self::ASSETS_UNIQUE_HANDLE,
            $url_to_stylesheet,
            [], // deps
            // Dummy version added to URL for cache busting purposes. We follow
            // so far the version of
            // https://github.com/Frameright/image-display-control-web-component
            '0.0.9'
        );
    }

    /**
     * Deliver the JavaScript code of the <image-display-control> web component
     * to the front-end.
     */
    public function serve_web_component_js() {
        $relative_path_to_js_assets = '../assets/js/build/';
        $js_script_name = 'index.js';
        $absolute_path_to_js_script = realpath(
            __DIR__ . '/' . $relative_path_to_js_assets . $js_script_name
        );
        Debug\assert_($absolute_path_to_js_script, 'Could not find js assets');

        $url_to_js_assets = $this->global_functions->plugin_dir_url(
            $absolute_path_to_js_script
        );
        $url_to_js_script = $url_to_js_assets . $js_script_name;

        $this->global_functions->wp_enqueue_script(
            self::ASSETS_UNIQUE_HANDLE,
            $url_to_js_script,
            [], // deps
            // Dummy version added to URL for cache busting purposes. We follow
            // so far the version of
            // https://github.com/Frameright/image-display-control-web-component
            '0.0.9',
            true // put just before </body> instead of </head>
        );
    }

    const ASSETS_UNIQUE_HANDLE = 'frameright';

    /**
     * Filter implementation called when rendering images, giving the
     * opportunity to the plugin to tweak the HTML <img> tag.
     *
     * If the image being rendered is an original image containing XMP Image
     * Regions, we will add image-display-control-related attributes to the
     * <img> tag.
     *
     * It doesn't assume that the <img> tag is the root tag of the HTML
     * snippet.
     *
     * @param string $original_html HTML snippet containing an <img> tag.
     * @param int    $attachment_id The image attachment ID. May be 0 in case
     *                              the image is not an attachment.
     * @return string Filter input/output in which the <img> tag may have
     *                received additional HTML attributes.
     */
    private function add_img_subtag_attributes($original_html, $attachment_id) {
        Debug\log(
            "Maybe adding attributes to <img> tag for attachment $attachment_id"
        );
        Debug\log("Original snippet: $original_html");

        /**
         * At this point $original_html looks like
         *
         *     [maybe some opening of parent elements]
         *         <img width="2000" height="1000"
         *              src="https://mywordpress.com/wp-content/uploads/2022/11/myimage.jpg"
         *              class="attachment-post-thumbnail size-post-thumbnail wp-post-image"
         *              alt="" decoding="async" loading="lazy"
         *              srcset="https://mywordpress.com/wp-content/uploads/2022/11/myimage.jpg 2000w,
         *                      https://mywordpress.com/wp-content/uploads/2022/11/myimage-300x150.jpg 300w,
         *                      https://mywordpress.com/wp-content/uploads/2022/11/myimage-1024x512.jpg 1024w,
         *                      https://mywordpress.com/wp-content/uploads/2022/11/myimage-768x384.jpg 768w,
         *                      https://mywordpress.com/wp-content/uploads/2022/11/myimage-1536x768.jpg 1536w"
         *              sizes="(max-width: 2000px) 100vw, 2000px"
         *         />
         *     [maybe some closing of parent elements]
         */
        $parsed_img_snippet = self::parse_img_snippet($original_html);
        $document = $parsed_img_snippet['document'];
        $root_element = $parsed_img_snippet['root'];
        $img_element = $parsed_img_snippet['img'];
        $src_attribute = $parsed_img_snippet['src'];

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
                return $original_html;
            }
        }

        $regions = $this->global_functions->get_post_meta(
            $attachment_id,
            'frameright_has_image_regions',
            true
        );
        if (!$regions) {
            Debug\log('Image has no relevant image regions, leaving unchanged');
            return $original_html;
        }
        Debug\log('Found relevant image regions: ' . print_r($regions, true));
        $regions_json = $this->global_functions->wp_json_encode($regions);
        Debug\assert_($regions_json, 'Could not serialize image regions');

        $img_idc_snippet = self::build_img_idc_snippet(
            $document,
            $root_element,
            $img_element,
            $regions_json
        );
        Debug\log("Resulting snippet: $img_idc_snippet");
        return $img_idc_snippet;
    }

    /**
     * Parse an HTML stippet containing an '<img src="..." />' tag.
     *
     * @param string $snippet HTML snippet to be parsed.
     * @return array Supported keys:
     *               * 'document':  Instance of \DOMDocument
     *               * 'root':      Instance of \DOMElement
     *               * 'img':   Instance of \DOMElement
     *               * 'src':       Value (string) of the src= attribute
     */
    private static function parse_img_snippet($snippet) {
        $document = new \DOMDocument();
        $success = $document->loadXML($snippet);
        Debug\assert_($success, 'Could not parse ' . $snippet);

        $root_element = $document->documentElement;
        Debug\assert_($root_element, 'Could not find root element');

        $img_elements = $document->getElementsByTagName('img');
        $num_img_elements = $img_elements->length;
        Debug\assert_(
            1 === $num_img_elements,
            "Expected exactly one <img> tag, found $num_img_elements instead"
        );

        $img_element = $img_elements->item(0);
        Debug\assert_($img_element, 'Could not find <img> element');

        $src_attribute = $img_element->attributes->getNamedItem('src')
            ->nodeValue;
        Debug\assert_($src_attribute, 'Could not find src= attribute');
        Debug\log("Image URL: $src_attribute");

        return [
            'document' => $document,
            'root' => $root_element,
            'img' => $img_element,
            'src' => $src_attribute,
        ];
    }

    /**
     * Build an HTML snippet containing an '<img is="image-display-control" />'
     * tag.
     *
     * @param \DOMDocument $document Document containing the original elements.
     *                               Will be altered as a side-effect, throw it
     *                               away afterwards.
     * @param \DOMElement  $root_element Original root element.
     * @param \DOMElement  $img_element Original <img> element. Will be altered
     *                                  as a side-effect, throw it away
     *                                  afterwards.
     * @param string       $regions JSON-serialized image regions.
     * @return string Resulting HTML snippet.
     */
    private static function build_img_idc_snippet(
        $document,
        $root_element,
        $img_element,
        $regions
    ) {
        $img_element->setAttribute('is', 'image-display-control');
        $img_element->setAttribute('data-image-regions', $regions);
        if (Debug\enabled()) {
            $img_element->setAttribute('data-loglevel', 'debug');
        }
        $img_idc_snippet = $document->saveHTML($root_element);
        Debug\assert_(
            $img_idc_snippet,
            'Could not generate HTML snippet containing <img is="image-display-control"> tag'
        );
        return $img_idc_snippet;
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
