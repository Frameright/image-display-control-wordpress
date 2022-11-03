<?php
/**
 * Tests for FramerightImageDisplayControl\Admin 's Filesystem class.
 *
 * @package FramerightImageDisplayControl\Tests\Admin
 */

require_once __DIR__ . '/../src/admin/filesystem.php';

/**
 * Tests for Filesystem.
 */
final class FilesystemTest extends PHPUnit\Framework\TestCase {
    // phpcs:disable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
    /**
     * Test setup.
     */
    protected function setUp(): void {
        $this->global_functions_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['wp_read_image_metadata', 'wp_unique_filename'])
            ->getMock();
    }
    // phpcs:enable

    /**
     * Test basename_to_name_and_extension().
     */
    public function test_basename_to_name_and_extension() {
        $method = new ReflectionMethod(
            'FramerightImageDisplayControl\Admin\Filesystem',
            'basename_to_name_and_extension'
        );
        $method->setAccessible(true);

        $input_basename = 'my.great.file.jpg';
        $expected_result = ['my.great.file', 'jpg'];
        $actual_result = $method->invoke(null, $input_basename);
        $this->assertSame($expected_result, $actual_result);

        $input_basename = 'myfile';
        $expected_result = ['myfile', ''];
        $actual_result = $method->invoke(null, $input_basename);
        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * Test name_and_extension_to_basename().
     */
    public function test_name_and_extension_to_basename() {
        $method = new ReflectionMethod(
            'FramerightImageDisplayControl\Admin\Filesystem',
            'name_and_extension_to_basename'
        );
        $method->setAccessible(true);

        $input_name = 'my.great.file';
        $input_extension = 'jpg';
        $expected_result = 'my.great.file.jpg';
        $actual_result = $method->invoke(null, $input_name, $input_extension);
        $this->assertSame($expected_result, $actual_result);

        $input_name = 'myfile';
        $input_extension = '';
        $expected_result = 'myfile';
        $actual_result = $method->invoke(null, $input_name, $input_extension);
        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * Test unique_target_file().
     */
    public function test_unique_target_file() {
        $input_source_path = '/absolute/path/to/img.jpg';

        $expected_result = [
            'path' => '/absolute/path/to/img-frameright.jpg',
            'basename' => 'img-frameright.jpg',
            'dirname' => '/absolute/path/to',
            'name' => 'img-frameright',
            'extension' => 'jpg',
        ];

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_unique_filename')
            ->with('/absolute/path/to', 'img-frameright.jpg')
            ->willReturn('img-frameright.jpg');

        $actual_result = (new FramerightImageDisplayControl\Admin\Filesystem(
            $this->global_functions_mock
        ))->unique_target_file($input_source_path, '-frameright');

        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * Test image_title() with metadata.
     */
    public function test_image_title_with_metadata() {
        $input_path = '/absolute/path/to/img.jpg';

        $expected_result = 'My title';

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_read_image_metadata')
            ->with($input_path)
            ->willReturn([
                'title' => $expected_result,
            ]);

        $actual_result = (new FramerightImageDisplayControl\Admin\Filesystem(
            $this->global_functions_mock
        ))->image_title($input_path);

        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * Test image_title() without metadata.
     */
    public function test_image_title_without_metadata() {
        $input_path = '/absolute/path/to/img.jpg';

        $expected_result = 'img';

        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_read_image_metadata')
            ->with($input_path)
            ->willReturn([
                'title' => '',
            ]);

        $actual_result = (new FramerightImageDisplayControl\Admin\Filesystem(
            $this->global_functions_mock
        ))->image_title($input_path);

        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * GlobalFunctions mock.
     *
     * @var Mock_stdClass
     */
    private $global_functions_mock;
}
