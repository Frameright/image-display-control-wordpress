<?php
/**
 * Tests for Frameright\Website 's WebsitePlugin class.
 *
 * @package Frameright\Tests\Website
 */

require_once __DIR__ . '/../src/website/website-plugin.php';
require_once __DIR__ . '/../src/vendor/autoload.php';

/**
 * Tests for WebsitePlugin.
 */
final class WebsitePluginTest extends PHPUnit\Framework\TestCase {
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

        new Frameright\Website\WebsitePlugin($this->global_functions_mock);
    }

    /**
     * Test replace_srcsets().
     */
    public function test_replace_srcsets() {
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
            100, // height
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
            ->willReturn([43, 44]);

        $this->global_functions_mock
            ->expects($this->exactly(2))
            ->method('wp_get_attachment_metadata')
            ->withConsecutive([43], [44])
            ->will(
                $this->onConsecutiveCalls(
                    [
                        'width' => 3000,
                        'height' => 500,
                        'sizes' => [],
                    ],
                    [
                        'width' => 1000,
                        'height' => 2000,
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
                    'https://mywordpress.com/wp-content/uploads/2022/10/hardcrop43.jpg',
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

        $actual_srcsets = (new Frameright\Website\WebsitePlugin(
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
     * GlobalFunctions mock.
     *
     * @var Mock_stdClass
     */
    private $global_functions_mock;
}
