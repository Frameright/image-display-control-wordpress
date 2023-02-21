<?php
/**
 * Tests for FramerightImageDisplayControl\Admin 's AdminPlugin class.
 *
 * @package FramerightImageDisplayControl\Tests\Admin
 */

require_once __DIR__ . '/../src/admin/admin-plugin.php';
require_once __DIR__ . '/../src/vendor/autoload.php';

/**
 * Tests for AdminPlugin.
 */
final class AdminPluginTest extends PHPUnit\Framework\TestCase {
    // phpcs:disable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
    /**
     * Test setup.
     */
    protected function setUp(): void {
        $this->global_functions_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods([
                'add_action',
                'add_filter',
                'add_post_meta',
                'is_wp_error',
                'wp_get_attachment_url',
                'wp_get_image_editor',
            ])
            ->getMock();
        $this->xmp_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['read_rectangle_cropping_metadata'])
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
            ->with('wp_handle_upload');

        new FramerightImageDisplayControl\Admin\AdminPlugin(
            $this->global_functions_mock,
            $this->xmp_mock
        );
    }

    /**
     * Test test_handle_file_upload() and set_attachment_meta().
     */
    public function test_handle_file_upload_and_set_attachment_meta() {
        $input_source_dirname = '/absolute/path/to';
        $input_source_basename = 'img.jpg';
        $input_source_path =
            $input_source_dirname . '/' . $input_source_basename;
        $input_source_url =
            'https://mywordpress.dev/wp-content/uploads/2022/10/' .
            $input_source_basename;
        $input_source_type = 'image/jpeg';
        $input_source_attachment_id = 43;

        $input_handle_file_upload_filter = [
            'file' => $input_source_path,
            'url' => $input_source_url,
            'type' => $input_source_type,
        ];

        $input_xmp_regions = [
            $this->create_mock_image_region(
                'region42',
                ['Region 42'],
                'rectangle',
                'relative',
                0.31,
                0.18,
                0.385,
                0.127
            ),
        ];
        $this->xmp_mock
            ->expects($this->once())
            ->method('read_rectangle_cropping_metadata')
            ->with($input_source_path)
            ->willReturn($input_xmp_regions);

        $image_editor_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['get_size'])
            ->getMock();
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_image_editor')
            ->with($input_source_path)
            ->willReturn($image_editor_mock);

        $expected_target_name = 'img-frameright-region42';
        $expected_target_basename = $expected_target_name . '.jpg';

        $image_editor_mock
            ->expects($this->once())
            ->method('get_size')
            ->willReturn([
                'width' => 507,
                'height' => 407,
            ]);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_attachment_url')
            ->with($input_source_attachment_id)
            ->willReturn($input_source_url);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('add_post_meta')
            ->with(
                $input_source_attachment_id,
                'frameright_has_image_regions',
                [
                    [
                        'id' => 'region42',
                        'names' => ['Region 42'],
                        'shape' => 'rectangle',
                        'unit' => 'relative',
                        'imageWidth' => 507,
                        'imageHeight' => 407,
                        'x' => 0.31,
                        'y' => 0.18,
                        'height' => 0.385,
                        'width' => 0.127,
                    ],
                ]
            )
            ->willReturn(83);

        $handle_file_upload_method = new ReflectionMethod(
            'FramerightImageDisplayControl\Admin\AdminPlugin',
            'handle_file_upload'
        );
        $set_attachment_meta_method = new ReflectionMethod(
            'FramerightImageDisplayControl\Admin\AdminPlugin',
            'set_attachment_meta'
        );

        $plugin_under_test = new FramerightImageDisplayControl\Admin\AdminPlugin(
            $this->global_functions_mock,
            $this->xmp_mock
        );

        $expected_result = $input_handle_file_upload_filter;
        $actual_result = $handle_file_upload_method->invoke(
            $plugin_under_test,
            $input_handle_file_upload_filter
        );
        $this->assertSame($expected_result, $actual_result);

        $set_attachment_meta_method->invoke(
            $plugin_under_test,
            $input_source_attachment_id
        );
    }

    /**
     * Test read_rectangle_cropping_metadata().
     */
    public function test_read_rectangle_cropping_metadata() {
        $input_path = '/absolute/path/to/img.jpg';

        $image_editor_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['get_size'])
            ->getMock();
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_image_editor')
            ->with($input_path)
            ->willReturn($image_editor_mock);

        $image_editor_mock
            ->expects($this->once())
            ->method('get_size')
            ->willReturn([
                'width' => 507,
                'height' => 407,
            ]);

        $input_xmp_regions = [
            $this->create_mock_image_region(
                'region42',
                ['Region 42'],
                'rectangle',
                'relative',
                0.31,
                0.18,
                0.385,
                0.127
            ),
        ];
        $this->xmp_mock
            ->expects($this->once())
            ->method('read_rectangle_cropping_metadata')
            ->with($input_path)
            ->willReturn($input_xmp_regions);

        $expected_result = [
            [
                'id' => 'region42',
                'names' => ['Region 42'],
                'shape' => 'rectangle',
                'unit' => 'relative',
                'imageWidth' => 507,
                'imageHeight' => 407,
                'x' => 0.31,
                'y' => 0.18,
                'height' => 0.385,
                'width' => 0.127,
            ],
        ];

        $method = new ReflectionMethod(
            'FramerightImageDisplayControl\Admin\AdminPlugin',
            'read_rectangle_cropping_metadata'
        );
        $method->setAccessible(true);
        $actual_result = $method->invoke(
            new FramerightImageDisplayControl\Admin\AdminPlugin(
                $this->global_functions_mock,
                $this->xmp_mock
            ),
            $input_path
        );

        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * Forges an instance of ImageRegion.
     *
     * @param string $id Region ID.
     * @param array  $names Region names in different languages.
     * @param string $shape 'rectangle'.
     * @param string $unit 'pixel' or 'relative'.
     * @param string $x Region coordinate.
     * @param string $y Region coordinate.
     * @param string $height Region height.
     * @param string $width Region width.
     * @return \CSD\Image\Metadata\Xmp\ImageRegion Mock.
     */
    private function create_mock_image_region(
        $id,
        $names,
        $shape,
        $unit,
        $x,
        $y,
        $height,
        $width
    ) {
        $result = new \CSD\Image\Metadata\Xmp\ImageRegion();
        $result->id = $id;
        $result->names = $names;
        $result->rbShape = $shape;
        $result->rbUnit = $unit;
        $result->rbXY = new \CSD\Image\Metadata\Xmp\Point($x, $y);
        $result->rbH = $height;
        $result->rbW = $width;
        return $result;
    }

    /**
     * GlobalFunctions mock.
     *
     * @var Mock_stdClass
     */
    private $global_functions_mock;

    /**
     * Xmp mock.
     *
     * @var Mock_stdClass
     */
    private $xmp_mock;
}
