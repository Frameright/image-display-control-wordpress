<?php
/**
 * Imported only when browsing admin panel.
 *
 * @package Frameright\Admin
 */

namespace Frameright\Admin;

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/global-functions.php';

/**
 * Implementation of the plugin when inside the admin panel.
 */
class AdminPlugin {
    /**
     * Constructor.
     *
     * @param GlobalFunctions $global_functions Mockable wrapper for calling
     *                                          global functions.
     */
    public function __construct($global_functions = null) {
        $this->global_functions = $global_functions
            ? $global_functions
            : new GlobalFunctions();

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

    const EXTENSION_SEPARATOR = '.';

    /**
     * Generate a non-existing file name/path for creating a copy/variant of a
     * given source file.
     *
     * @param string $source_path Absolute path of the source file.
     * @return array Supported keys:
     *               * 'path':      '/absolute/path/to/img-frameright.jpg'
     *               * 'basename':  'img-frameright.jpg'
     *               * 'dirname':   '/absolute/path/to'
     *               * 'name':      'img-frameright'
     *               * 'extension': 'jpg'
     */
    private static function unique_target_file($source_path) {
        $source_basename = basename($source_path);
        $source_dirname = dirname($source_path);

        $source_basename_items = explode(
            self::EXTENSION_SEPARATOR,
            $source_basename
        );
        Debug\assert_(
            count($source_basename_items) >= 2,
            "'$source_basename' should contain at least one '" .
                self::EXTENSION_SEPARATOR .
                "'"
        );
        $source_extension = array_pop($source_basename_items);
        $source_name = implode(
            self::EXTENSION_SEPARATOR,
            $source_basename_items
        );

        $target_name = $source_name . '-frameright';
        $target_basename =
            $target_name . self::EXTENSION_SEPARATOR . $source_extension;
        $target_path = $source_dirname . DIRECTORY_SEPARATOR . $target_basename;

        $result = [
            'path' => $target_path,
            'basename' => $target_basename,
            'dirname' => $source_dirname,
            'name' => $target_name,
            'extension' => $source_extension,
        ];

        return $result;
    }

    /**
     * Create hardcropped versions of a given source image.
     *
     * @param string $source_image_path Absolute path of the source image.
     */
    private function create_hardcrops($source_image_path) {
        Debug\log("Creating hardcrops of $source_image_path ...");

        $target_image_file = self::unique_target_file($source_image_path);
        Debug\log('Target file: ' . print_r($target_image_file, true));
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
