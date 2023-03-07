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
                'is_admin',
                'plugin_dir_url',
                'wp_enqueue_script',
                'wp_enqueue_style',
                'wp_get_additional_image_sizes',
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

        $this->global_functions_mock
            ->expects($this->exactly(3))
            ->method('add_filter')
            ->withConsecutive(
                ['wp_get_attachment_metadata'],
                ['wp_content_img_tag'],
                ['post_thumbnail_html']
            );

        new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        );
    }

    /**
     * Test blocklist_hardcrops() .
     */
    public function test_blocklist_hardcrops() {
        $input_data = [
            'width' => 3000,
            'height' => 2000,
            'file' => '2022/11/myimage.jpg',
            'sizes' => [
                'medium' => [
                    'file' => 'myimage-300x200.jpg',
                    'width' => 300,
                    'height' => 200,
                ],
                'medium_large' => [
                    'file' => 'myimage-768x512.jpg',
                    'width' => 768,
                    'height' => 512,
                ],
                'large' => [
                    'file' => 'myimage-1024x683.jpg',
                    'width' => 1024,
                    'height' => 683,
                ],
                'thumbnail' => [
                    'file' => 'myimage-150x150.jpg',
                    'width' => 150,
                    'height' => 150,
                ],
                'mytheme-1000-1000' => [
                    'file' => 'myimage-1000x1000.jpg',
                    'width' => 1000,
                    'height' => 1000,
                ],
                'mytheme-1000-auto' => [
                    'file' => 'myimage-1000x667.jpg',
                    'width' => 1000,
                    'height' => 667,
                ],
            ],
        ];
        $input_attachment_id = 42;

        $this->global_functions_mock
            ->expects($this->once())
            ->method('is_admin')
            ->with()
            ->willReturn(false);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('get_post_meta')
            ->with(42, 'frameright_has_image_regions')
            ->willReturn('an array of regions');

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_additional_image_sizes')
            ->with()
            ->willReturn([
                'mytheme-1000-1000' => 'some data',
                'mytheme-1000-auto' => 'some data',
            ]);

        $expected_data = [
            'width' => 3000,
            'height' => 2000,
            'file' => '2022/11/myimage.jpg',
            'sizes' => [
                'medium' => [
                    'file' => 'myimage-300x200.jpg',
                    'width' => 300,
                    'height' => 200,
                ],
                'medium_large' => [
                    'file' => 'myimage-768x512.jpg',
                    'width' => 768,
                    'height' => 512,
                ],
                'large' => [
                    'file' => 'myimage-1024x683.jpg',
                    'width' => 1024,
                    'height' => 683,
                ],
                'thumbnail' => [
                    'file' => 'myimage-150x150.jpg',
                    'width' => 150,
                    'height' => 150,
                ],
                'mytheme-1000-auto' => [
                    'file' => 'myimage-1000x667.jpg',
                    'width' => 1000,
                    'height' => 667,
                ],
            ],
        ];

        $filtered_data = (new FramerightImageDisplayControl\Render\RenderPlugin(
            $this->global_functions_mock
        ))->blocklist_hardcrops($input_data, $input_attachment_id);

        $this->assertSame($expected_data, $filtered_data);
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
            'data-image-regions="a json-encoded array of regions" ' .
            'data-css-contain-fallback="overflow-hidden" ' .
            'data-avoid-no-region="off">';

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
            'data-image-regions="a json-encoded array of regions" ' .
            'data-css-contain-fallback="overflow-hidden" ' .
            'data-avoid-no-region="off"></div>';

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
