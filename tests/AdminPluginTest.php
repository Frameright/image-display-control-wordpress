<?php
/**
 * Tests for Frameright\Admin 's AdminPlugin class.
 *
 * @package Frameright\Tests\Admin
 */

require_once __DIR__ . '/../admin/admin-plugin.php';

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
                'is_wp_error',
                'wp_get_image_editor',
                'wp_insert_attachment',
            ])
            ->getMock();
        $this->filesystem_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['unique_target_file'])
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

        new Frameright\Admin\AdminPlugin(
            $this->global_functions_mock,
            $this->filesystem_mock
        );
    }

    /**
     * Test create_hardcrops().
     */
    public function test_create_hardcrops() {
        $input_source_path = '/absolute/path/to/img.jpg';

        $image_editor_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['save'])
            ->getMock();
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_image_editor')
            ->with('/absolute/path/to/img.jpg')
            ->willReturn($image_editor_mock);

        $this->filesystem_mock
            ->expects($this->once())
            ->method('unique_target_file')
            ->with('/absolute/path/to/img.jpg')
            ->willReturn([
                'path' => '/absolute/path/to/img-frameright.jpg',
                'basename' => 'img-frameright.jpg',
                'dirname' => '/absolute/path/to',
                'name' => 'img-frameright',
                'extension' => 'jpg',
            ]);

        $image_editor_mock
            ->expects($this->once())
            ->method('save')
            ->with('/absolute/path/to/img-frameright.jpg')
            ->willReturn([
                'path' => '/absolute/path/to/img-frameright.jpg',
                'file' => 'img-frameright.jpg',
                'width' => 2395,
                'height' => 1807,
                'mime-type' => 'image/jpeg',
                'filesize' => 622369,
            ]);

        $method = new ReflectionMethod(
            'Frameright\Admin\AdminPlugin',
            'create_hardcrops'
        );
        $method->setAccessible(true);
        $method->invoke(
            new Frameright\Admin\AdminPlugin(
                $this->global_functions_mock,
                $this->filesystem_mock
            ),
            $input_source_path
        );
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
}
