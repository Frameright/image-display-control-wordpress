<?php
/**
 * Imported only when browsing admin panel.
 *
 * @package Frameright\Admin
 */

namespace Frameright\Admin;

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/filesystem.php';
require_once __DIR__ . '/global-functions.php';

/**
 * Implementation of the plugin when inside the admin panel.
 */
class AdminPlugin {
    /**
     * Constructor.
     *
     * @param Mock_stdClass $global_functions_mock Mock of GlobalFunctions if
     *                                             running tests.
     * @param Mock_stdClass $filesystem_mock Mock of Filesystem if running
     *                                       tests.
     */
    public function __construct(
        $global_functions_mock = null,
        $filesystem_mock = null
    ) {
        $this->global_functions = $global_functions_mock
            ? $global_functions_mock
            : new GlobalFunctions();
        $this->filesystem = $filesystem_mock
            ? $filesystem_mock
            : new Filesystem($this->global_functions);

        $this->global_functions->add_filter('wp_handle_upload', [
            $this,
            'handle_upload',
        ]);
    }

    /**
     * Filter called when an image gets uploaded to the media library.
     *
     * @param array $data Filter input.
     */
    public function handle_upload($data) {
        Debug\log('An image got uploaded: ' . print_r($data, true));
        $this->create_hardcrops($data['file']);
        return $data;
    }

    /**
     * Create hardcropped versions of a given source image.
     *
     * @param string $source_image_path Absolute path of the source image.
     */
    private function create_hardcrops($source_image_path) {
        Debug\log("Creating hardcrops of $source_image_path ...");

        $target_image_file = $this->filesystem->unique_target_file(
            $source_image_path
        );
        Debug\log('Target file: ' . print_r($target_image_file, true));
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;

    /**
     * Collection of file-related helper functions.
     *
     * @var Filesystem
     */
    private $filesystem;
}
