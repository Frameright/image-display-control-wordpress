<?php
/**
 * Tests for Frameright\Admin 's Filesystem class.
 *
 * @package Frameright\Tests\Admin
 */

require_once __DIR__ . '/../admin/filesystem.php';

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
            ->addMethods(['wp_unique_filename'])
            ->getMock();
    }
    // phpcs:enable

    /**
     * Test basename_to_name_and_extension().
     */
    public function test_basename_to_name_and_extension() {
        $input_basename = 'my.great.file.jpg';

        $expected_result = ['my.great.file', 'jpg'];

        $method = new ReflectionMethod(
            'Frameright\Admin\Filesystem',
            'basename_to_name_and_extension'
        );
        $method->setAccessible(true);
        $actual_result = $method->invoke(null, $input_basename);

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

        $method = new ReflectionMethod(
            'Frameright\Admin\Filesystem',
            'unique_target_file'
        );
        $method->setAccessible(true);
        $actual_result = $method->invoke(
            new Frameright\Admin\Filesystem($this->global_functions_mock),
            $input_source_path
        );

        $this->assertSame($expected_result, $actual_result);
    }

    /**
     * GlobalFunctions mock.
     *
     * @var Mock_stdClass
     */
    private $global_functions_mock;
}
