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
                'add_post_meta',
                'is_wp_error',
                'wp_generate_attachment_metadata',
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
        $input_source_dirname = '/absolute/path/to';
        $input_source_basename = 'img.jpg';
        $input_source_path =
            $input_source_dirname . '/' . $input_source_basename;

        $image_editor_mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['save'])
            ->getMock();
        $this->global_functions_mock
            ->expects($this->once())
            ->method('wp_get_image_editor')
            ->with($input_source_path)
            ->willReturn($image_editor_mock);

        $expected_target_name = 'img-frameright';
        $expected_target_basename = $expected_target_name . '.jpg';
        $expected_target_path =
            $input_source_dirname . '/' . $expected_target_basename;
        $this->filesystem_mock
            ->expects($this->once())
            ->method('unique_target_file')
            ->with($input_source_path)
            ->willReturn([
                'path' => $expected_target_path,
                'basename' => $expected_target_basename,
                'dirname' => $input_source_dirname,
                'name' => $expected_target_name,
                'extension' => 'jpg',
            ]);

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
                    'post_title' => $expected_target_name,
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
