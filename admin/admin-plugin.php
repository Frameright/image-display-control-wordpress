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
     * Split name and extension from a file name.
     *
     * @param string $basename File name, e.g. 'my.great.file.jpg'.
     * @return array ['my.great.file', 'jpg'] .
     */
    private static function basename_to_name_and_extension($basename) {
        $basename_items = explode(self::EXTENSION_SEPARATOR, $basename);
        Debug\assert_(
            count($basename_items) >= 2,
            "'$basename' should contain at least one '" .
                self::EXTENSION_SEPARATOR .
                "'"
        );

        $extension = array_pop($basename_items);
        $name = implode(self::EXTENSION_SEPARATOR, $basename_items);

        return [$name, $extension];
    }

    /**
     * Create hardcropped versions of a given source image.
     *
     * @param string $source_image_path Absolute path of the source image.
     */
    private function create_hardcrops($source_image_path) {
        Debug\log("Creating hardcrops of $source_image_path ...");

        $target_image_file = $this->unique_target_file($source_image_path);
        Debug\log('Target file: ' . print_r($target_image_file, true));
    }

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
    private function unique_target_file($source_path) {
        $source_basename = basename($source_path);
        $source_dirname = dirname($source_path);

        $source_basename_items = self::basename_to_name_and_extension(
            $source_basename
        );
        $source_name = $source_basename_items[0];
        $source_extension = $source_basename_items[1];

        $target_name = $source_name . '-frameright';
        $target_basename =
            $target_name . self::EXTENSION_SEPARATOR . $source_extension;

        // In case this file already exists, ask WordPress to find a new
        // filename in that same folder that doesn't exist yet.
        $target_basename = $this->global_functions->wp_unique_filename(
            $source_dirname,
            $target_basename
        );
        $target_name = self::basename_to_name_and_extension(
            $target_basename
        )[0];

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
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
