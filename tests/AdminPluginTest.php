<?php
/**
 * Tests for Frameright\Admin 's AdminPlugin class.
 *
 * @package Frameright\Tests\Admin
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
                'wp_generate_attachment_metadata',
                'wp_get_image_editor',
                'wp_insert_attachment',
            ])
            ->getMock();
        $this->filesystem_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['image_title', 'unique_target_file'])
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
            ->expects($this->exactly(2))
            ->method('add_filter')
            ->withConsecutive(['wp_handle_upload'], ['wp_read_image_metadata']);

        new Frameright\Admin\AdminPlugin(
            $this->global_functions_mock,
            $this->filesystem_mock,
            $this->xmp_mock
        );
    }

    /**
     * Test populate_image_metadata().
     */
    public function test_populate_image_metadata() {
        $input_meta = [
            'aperture' => 0,
            'camera' => 'Canon EOS 5D Mark III',
            'created_timestamp' => 1510309891,
            'focal_length' => 39,
            'iso' => 800,
            'shutter_speed' => 0.016666666666667,
            'orientation' => 1,
        ];
        $input_file = '/absolute/path/to/img.jpg';

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
            ->with($input_file)
            ->willReturn($input_xmp_regions);

        $expected_meta = [
            'aperture' => 0,
            'camera' => 'Canon EOS 5D Mark III',
            'created_timestamp' => 1510309891,
            'focal_length' => 39,
            'iso' => 800,
            'shutter_speed' => 0.016666666666667,
            'orientation' => 1,
            'image_regions' => [
                [
                    'id' => 'region42',
                    'names' => ['Region 42'],
                    'shape' => 'rectangle',
                    'unit' => 'relative',
                    'x' => 0.31,
                    'y' => 0.18,
                    'height' => 0.385,
                    'width' => 0.127,
                ],
            ],
        ];

        $actual_meta = (new Frameright\Admin\AdminPlugin(
            $this->global_functions_mock,
            $this->filesystem_mock,
            $this->xmp_mock
        ))->populate_image_metadata($input_meta, $input_file, null, null, null);

        $this->assertSame($expected_meta, $actual_meta);
    }

    /**
     * Test create_hardcrops().
     */
    public function test_create_hardcrops() {
        $input_source_dirname = '/absolute/path/to';
        $input_source_basename = 'img.jpg';
        $input_source_path =
            $input_source_dirname . '/' . $input_source_basename;

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
            ->addMethods(['save'])
            ->getMock();
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_image_editor')
            ->with($input_source_path)
            ->willReturn($image_editor_mock);

        $expected_target_name = 'img-frameright-region42';
        $expected_target_basename = $expected_target_name . '.jpg';
        $expected_target_path =
            $input_source_dirname . '/' . $expected_target_basename;
        $this->filesystem_mock
            ->expects($this->once())
            ->method('unique_target_file')
            ->with($input_source_path, '-frameright-region42')
            ->willReturn([
                'path' => $expected_target_path,
                'basename' => $expected_target_basename,
                'dirname' => $input_source_dirname,
                'name' => $expected_target_name,
                'extension' => 'jpg',
            ]);

        $this->filesystem_mock
            ->expects($this->once())
            ->method('image_title')
            ->with($input_source_path)
            ->willReturn('My title');

        $image_editor_mock
            ->expects($this->once())
            ->method('save')
            ->with($expected_target_path)
            ->willReturn([
                'path' => $expected_target_path,
                'file' => $expected_target_basename,
                'width' => 2395,
                'height' => 1807,
                'mime-type' => 'image/jpeg',
                'filesize' => 622369,
            ]);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_insert_attachment')
            ->with(
                [
                    'post_mime_type' => 'image/jpeg',
                    'post_title' => '[frameright:hardcrop] My title - region42',
                ],
                $expected_target_path
            )
            ->willReturn(42);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('add_post_meta')
            ->with(42, 'frameright', true, true);

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_generate_attachment_metadata')
            ->with(42, $expected_target_path);

        $method = new ReflectionMethod(
            'Frameright\Admin\AdminPlugin',
            'create_hardcrops'
        );
        $method->setAccessible(true);
        $method->invoke(
            new Frameright\Admin\AdminPlugin(
                $this->global_functions_mock,
                $this->filesystem_mock,
                $this->xmp_mock
            ),
            $input_source_path
        );
    }

    /**
     * Test read_rectangle_cropping_metadata().
     */
    public function test_read_rectangle_cropping_metadata() {
        $input_path = '/absolute/path/to/img.jpg';

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
                'x' => 0.31,
                'y' => 0.18,
                'height' => 0.385,
                'width' => 0.127,
            ],
        ];

        $method = new ReflectionMethod(
            'Frameright\Admin\AdminPlugin',
            'read_rectangle_cropping_metadata'
        );
        $method->setAccessible(true);
        $actual_result = $method->invoke(
            new Frameright\Admin\AdminPlugin(
                $this->global_functions_mock,
                $this->filesystem_mock,
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
     * @param string $unit E.g. 'relative'.
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
     * Filesystem mock.
     *
     * @var Mock_stdClass
     */
    private $filesystem_mock;

    /**
     * Xmp mock.
     *
     * @var Mock_stdClass
     */
    private $xmp_mock;
}
