<?php
/**
 * Imported only when browsing as a visitor.
 *
 * @package Frameright\Website
 */

namespace Frameright\Website;

require_once __DIR__ . '/../debug.php';
use Frameright\Debug;
require_once __DIR__ . '/../global-functions.php';
use Frameright\GlobalFunctions;

/**
 * Implementation of the plugin when outside the admin panel.
 */
class WebsitePlugin {
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

        $this->global_functions->add_filter(
            'render_block_data',
            [$this, 'render_block_data'],
            10, // default priority
            3 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_img_tag_add_width_and_height_attr',
            [$this, 'wp_img_tag_add_width_and_height_attr'],
            10, // default priority
            4 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_get_attachment_metadata',
            [$this, 'wp_get_attachment_metadata'],
            10, // default priority
            2 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_image_src_get_dimensions',
            [$this, 'wp_image_src_get_dimensions'],
            10, // default priority
            4 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_calculate_image_srcset_meta',
            [$this, 'wp_calculate_image_srcset_meta'],
            10, // default priority
            4 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_calculate_image_srcset',
            [$this, 'wp_calculate_image_srcset'],
            10, // default priority
            5 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_calculate_image_sizes',
            [$this, 'wp_calculate_image_sizes'],
            10, // default priority
            5 // number of arguments
        );
        $this->global_functions->add_filter(
            'wp_content_img_tag',
            [$this, 'wp_content_img_tag'],
            10, // default priority
            3 // number of arguments
        );
    }

    public function render_block_data(
        $parsed_block,
        $source_block,
        $parent_block
    ) {
        if ('core/image' === $source_block['blockName']) {
            Debug\log('begin render_block_data');

            // LA_TEMP:
            /*$parsed_block['innerContent'][0] = str_replace(
                'IPTC-PhotometadataRef-Std2021.1-1024x512.jpg',
                'IPTC-PhotometadataRef-Std2021.1-frameright-persltr2.jpg',
                $parsed_block['innerContent'][0]
            );*/

            Debug\log('parsed_block: ' . print_r($parsed_block, true));
            Debug\log('source_block: ' . print_r($source_block, true));
            Debug\log('parent_block: ' . print_r($parent_block, true));
            Debug\log('end render_block_data');
        }

        return $parsed_block;
    }

    public function wp_img_tag_add_width_and_height_attr(
        $value,
        $image,
        $context,
        $attachment_id
    ) {
        Debug\log('begin wp_img_tag_add_width_and_height_attr');
        Debug\log('value: ' . print_r($value, true));
        Debug\log('image: ' . print_r($image, true));
        Debug\log('context: ' . print_r($context, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));
        Debug\log('end wp_img_tag_add_width_and_height_attr');
        return $value;
    }

    public function wp_get_attachment_metadata($data, $attachment_id) {
        Debug\log('begin wp_get_attachment_metadata');
        Debug\log('data: ' . print_r($data, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));

        $hardcrop_meta = wp_get_attachment_metadata(211, true);
        Debug\log('hardcrop_meta: ' . print_r($hardcrop_meta, true));

        Debug\log('end wp_get_attachment_metadata');

        // LA_TODO this has a large wanted impact but things go wrong down the
        // line because the entire algorithm stills remembers the former
        // attachment ID and uses it to compare URLs. So it doesn't work, in
        // the end all srcset are gone from the <img> element.
        // return $hardcrop_meta;

        return $data;
    }

    public function wp_image_src_get_dimensions(
        $dimensions,
        $image_src,
        $image_meta,
        $attachment_id
    ) {
        Debug\log('begin wp_image_src_get_dimensions');
        Debug\log('dimensions: ' . print_r($dimensions, true));
        Debug\log('image_src: ' . print_r($image_src, true));
        Debug\log('image_meta: ' . print_r($image_meta, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));
        Debug\log('end wp_image_src_get_dimensions');
        return $dimensions;
    }

    public function wp_calculate_image_srcset_meta(
        $image_meta,
        $size_array,
        $image_src,
        $attachment_id
    ) {
        Debug\log('begin wp_calculate_image_srcset_meta');

        // LA_TODO has an effect down the line on the largest srcset:
        // $image_meta['file'] = '2022/10/IPTC-PhotometadataRef-Std2021.1-frameright-persltr2.jpg';

        Debug\log('image_meta: ' . print_r($image_meta, true));
        Debug\log('size_array: ' . print_r($size_array, true));
        Debug\log('image_src: ' . print_r($image_src, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));

        $hardcrop_meta = wp_get_attachment_metadata(211);
        Debug\log('hardcrop_meta: ' . print_r($hardcrop_meta, true));

        Debug\log('end wp_calculate_image_srcset_meta');

        // LA_TODO doesn't work, same as with wp_get_attachment_metadata, all
        // srcset then vanish from the resulting <img>.
        // return $hardcrop_meta;

        return $image_meta;
    }

    public function wp_calculate_image_srcset(
        $sources,
        $size_array,
        $image_src,
        $image_meta,
        $attachment_id
    ) {
        Debug\log('begin wp_calculate_image_srcset');

        // Successfully modifies all srcset but not the src
        foreach ($sources as $key => $_) {
            //LA_TODO $sources[$key]['url'] = 'https://wordpress.lourot.dev/wp-content/uploads/2022/10/IPTC-PhotometadataRef-Std2021.1-frameright-persltr2.jpg';
        }

        Debug\log('sources: ' . print_r($sources, true));
        Debug\log('size_array: ' . print_r($size_array, true));
        Debug\log('image_src: ' . print_r($image_src, true));
        Debug\log('image_meta: ' . print_r($image_meta, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));
        Debug\log('end wp_calculate_image_srcset');
        return $sources;
    }

    public function wp_calculate_image_sizes(
        $sizes,
        $size,
        $image_src,
        $image_meta,
        $attachment_id
    ) {
        Debug\log('begin wp_calculate_image_sizes');
        Debug\log('sizes: ' . print_r($sizes, true));
        Debug\log('size: ' . print_r($size, true));
        Debug\log('image_src: ' . print_r($image_src, true));
        Debug\log('image_meta: ' . print_r($image_meta, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));
        Debug\log('end wp_calculate_image_sizes');
        return $sizes;
    }

    public function wp_content_img_tag(
        $filtered_image,
        $context,
        $attachment_id
    ) {
        Debug\log('begin wp_content_img_tag');
        Debug\log('filtered_image: ' . print_r($filtered_image, true));
        Debug\log('context: ' . print_r($context, true));
        Debug\log('attachment_id: ' . print_r($attachment_id, true));
        Debug\log('end wp_content_img_tag');
        return $filtered_image;
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
