<?php
/**
 * Tests for Frameright\Admin .
 *
 * @package Frameright\Tests\Admin
 */

require_once __DIR__ . '/../admin/admin-plugin.php';

/**
 * Tests for Frameright\Admin\AdminPlugin .
 */
final class AdminPluginTest extends PHPUnit\Framework\TestCase {
    /**
     * Test constructor.
     */
    public function test_constructor() {
        $global_functions_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['add_filter'])
            ->getMock();
        $global_functions_mock
            ->expects($this->once())
            ->method('add_filter')
            ->with('wp_handle_upload');

        new Frameright\Admin\AdminPlugin($global_functions_mock);
    }

    /**
     * Test AdminPlugin::unique_target_file().
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

        $method = new ReflectionMethod(
            'Frameright\Admin\AdminPlugin',
            'unique_target_file'
        );
        $method->setAccessible(true);
        $actual_result = $method->invoke(null, $input_source_path);

        $this->assertSame($expected_result, $actual_result);
    }
}
