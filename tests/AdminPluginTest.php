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
            ->addMethods(['add_filter'])
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

        $this->filesystem_mock
            ->expects($this->once())
            ->method('unique_target_file')
            ->with('/absolute/path/to/img.jpg');

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
