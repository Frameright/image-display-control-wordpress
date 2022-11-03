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
                'add_filter',
                'get_post_meta',
                'wp_get_attachment_metadata',
                'wp_get_attachment_url',
                'wp_get_registered_image_subsizes',
            ])
            ->getMock();
    }
    // phpcs:enable

    /**
     * Test constructor.
     */
    public function test_constructor() {
        $this->global_functions_mock
            ->expects($this->once())
            ->method('add_filter')
            ->with('wp_calculate_image_srcset');

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
     * GlobalFunctions mock.
     *
     * @var Mock_stdClass
     */
    private $global_functions_mock;
}
