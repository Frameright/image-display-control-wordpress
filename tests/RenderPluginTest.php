<?php
/**
 * Tests for FramerightImageDisplayControl\Render 's RenderPlugin class.
 *
 * @package FramerightImageDisplayControl\Tests\Render
 */

require_once __DIR__ . '/../src/render/render-plugin.php';
require_once __DIR__ . '/../src/vendor/autoload.php';

/**
 * Tests for RenderPlugin.
 */
final class RenderPluginTest extends PHPUnit\Framework\TestCase {
    // phpcs:disable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
    /**
     * Test setup.
     */
    protected function setUp(): void {
        $this->global_functions_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods([
                'add_action',
                'add_filter',
                'attachment_url_to_postid',
                'get_post_meta',
                'plugin_dir_url',
                'wp_enqueue_script',
                'wp_enqueue_style',
                'wp_get_attachment_metadata',
                'wp_get_attachment_url',
                'wp_get_registered_image_subsizes',
                'wp_json_encode',
            ])
            ->getMock();
    }
    // phpcs:enable

    /**
     * Test constructor.
     */
    public function test_constructor() {
        $this->global_functions_mock
            ->expects($this->exactly(2))
            ->method('add_action')
            ->with('wp_enqueue_scripts');

        if (
            FramerightImageDisplayControl\Render\RenderPlugin::LEGACY_HARDCROP_MODE
        ) {
            $this->global_functions_mock
                ->expects($this->once())
                ->method('add_filter')
                ->with('wp_calculate_image_srcset');
        } else {
            $this->global_functions_mock
                ->expects($this->exactly(2))
                ->method('add_filter')
                ->withConsecutive(
                    ['wp_content_img_tag'],
                    ['post_thumbnail_html']
                );
        }

        new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        );
    }

    /**
     * Test replace_srcsets() in the case where the original image has a better
     * hardcrop.
     */
    public function test_replace_srcsets_with_better_hardcrop() {
        $input_container_sizes = [
            'thumbnail' => [
                'crop' => true,
                'width' => 150,
            ],
            'medium' => [
                'crop' => false,
                'width' => 512,
            ],
            'large' => [
                'crop' => false,
                'width' => 1024,
            ],
        ];
        $input_container_size = [
            500, // width
            500, // height
        ];
        $input_original_image_meta = [
            'width' => 3000,
            'height' => 2000,
        ];
        $input_attachment_id = 42;

        $this->global_functions_mock
            ->expects($this->once())
            ->method('get_post_meta')
            ->with($input_attachment_id, 'frameright_has_hardcrops')
            ->willReturn([43, 44, 45]);

        $this->global_functions_mock
            ->expects($this->exactly(3))
            ->method('wp_get_attachment_metadata')
            ->withConsecutive([43], [44], [45])
            ->will(
                $this->onConsecutiveCalls(
                    [
                        'width' => 600,
                        'height' => 500,
                        'sizes' => [
                            'medium' => [
                                'file' => 'hardcrop43-medium.jpg',
                            ],
                        ],
                    ],
                    [
                        'width' => 1000,
                        'height' => 2000,
                        'sizes' => [],
                    ],
                    [
                        'width' => 500,
                        'height' => 620,
                        'sizes' => [],
                    ]
                )
            );

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_attachment_url')
            ->with(43)
            ->willReturn(
                'https://mywordpress.com/wp-content/uploads/2022/10/hardcrop43.jpg'
            );

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_registered_image_subsizes')
            ->willReturn($input_container_sizes);

        $expected_srcsets = [
            512 => [
                'url' =>
                    'https://mywordpress.com/wp-content/uploads/2022/10/hardcrop43-medium.jpg',
                'descriptor' => 'w',
                'value' => 512,
            ],
            1024 => [
                'url' =>
                    'https://mywordpress.com/wp-content/uploads/2022/10/hardcrop43.jpg',
                'descriptor' => 'w',
                'value' => 1024,
            ],
        ];

        $actual_srcsets = (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->replace_srcsets(
            null,
            $input_container_size,
            null,
            $input_original_image_meta,
            $input_attachment_id
        );

        $this->assertSame($expected_srcsets, $actual_srcsets);
    }

    /**
     * Test replace_srcsets() in the case where the original image has no
     * hardcrop.
     */
    public function test_replace_srcsets_without_hardcrop() {
        $input_attachment_id = 42;

        $this->global_functions_mock
            ->expects($this->once())
            ->method('get_post_meta')
            ->with($input_attachment_id, 'frameright_has_hardcrops')
            ->willReturn([]);

        $expected_srcsets = null;

        $actual_srcsets = (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->replace_srcsets(null, null, null, null, $input_attachment_id);

        $this->assertSame($expected_srcsets, $actual_srcsets);
    }

    /**
     * Test tweak_img_tag() .
     */
    public function test_tweak_img_tag() {
        $input_image_url =
            'https://mywordpress.com/wp-content/uploads/2022/11/myimage.jpg';
        $input_tag =
            '<img width="2000" height="1000" src="' .
            $input_image_url .
            '" class="wp-post-image" />';

        $this->global_functions_mock
            ->expects($this->once())
            ->method('attachment_url_to_postid')
            ->with($input_image_url)
            ->willReturn(42);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('get_post_meta')
            ->with(42, 'frameright_has_image_regions')
            ->willReturn('an array of regions');

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_json_encode')
            ->with('an array of regions')
            ->willReturn('a json-encoded array of regions');

        $expected_tag =
            '<img width="2000" height="1000" src="' .
            $input_image_url .
            '" class="wp-post-image" is="image-display-control" ' .
            'data-image-regions="a json-encoded array of regions">';

        $actual_tag = (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->tweak_img_tag($input_tag, null, 0);

        $this->assertSame($expected_tag, $actual_tag);
    }

    /**
     * Test tweak_thumbnail_html() .
     */
    public function test_tweak_thumbnail_html() {
        $input_image_url =
            'https://mywordpress.com/wp-content/uploads/2022/11/myimage.jpg';
        $input_tag =
            '<div class="thumbnail"><img width="2000" height="1000" src="' .
            $input_image_url .
            '" class="wp-post-image" /></div>';

        $this->global_functions_mock
            ->expects($this->once())
            ->method('get_post_meta')
            ->with(42, 'frameright_has_image_regions')
            ->willReturn('an array of regions');

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_json_encode')
            ->with('an array of regions')
            ->willReturn('a json-encoded array of regions');

        $expected_tag =
            '<div class="thumbnail"><img width="2000" height="1000" src="' .
            $input_image_url .
            '" class="wp-post-image" is="image-display-control" ' .
            'data-image-regions="a json-encoded array of regions"></div>';

        $actual_tag = (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->tweak_thumbnail_html($input_tag, null, 42, null, null);

        $this->assertSame($expected_tag, $actual_tag);
    }

    /**
     * Test serve_css() .
     */
    public function test_serve_css() {
        $input_url_to_css_assets =
            'https://mywordpress.com/wp-content/plugins/frameright/src/assets/css/';
        $this->global_functions_mock
            ->expects($this->once())
            ->method('plugin_dir_url')
            ->willReturn($input_url_to_css_assets);

        $expected_url_to_stylesheet =
            $input_url_to_css_assets . 'frameright.css';
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_enqueue_style')
            ->with('frameright', $expected_url_to_stylesheet);

        (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->serve_css();
    }

    /**
     * Test serve_web_component_js() .
     */
    public function test_serve_web_component_js() {
        $input_url_to_js_assets =
            'https://mywordpress.com/wp-content/plugins/frameright/src/assets/js/build/';
        $this->global_functions_mock
            ->expects($this->once())
            ->method('plugin_dir_url')
            ->willReturn($input_url_to_js_assets);

        $expected_url_to_js_script = $input_url_to_js_assets . 'index.js';
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_enqueue_script')
            ->with('frameright', $expected_url_to_js_script);

        (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->serve_web_component_js();
    }

    /**
     * GlobalFunctions mock.
     *
     * @var Mock_stdClass
     */
    private $global_functions_mock;
}
