<?php
/**
 * File-related helper functions.
 *
 * @package Frameright\Admin
 */

namespace Frameright\Admin;

require_once __DIR__ . '/global-functions.php';

/**
 * Collection of file-related helper functions. This class has conceptually no
 * state, so all methods can be understood as static.
 */
class Filesystem {
    /**
     * Constructor.
     *
     * @param GlobalFunctions $global_functions Mockable wrapper for calling
     *                                          global functions.
     */
    public function __construct($global_functions) {
        $this->global_functions = $global_functions;
    }

    /**
     * Generate a non-existing file name/path for creating a copy/variant of a
     * given source file.
     *
     * @param string $source_path Absolute path of the source file, e.g.
     *                            '/absolute/path/to/img.jpg'.
     * @param string $basename_suffix Suffix to be appended to the original
     *                                basename, e.g. '-frameright'.
     * @return array Supported keys:
     *               * 'path':      '/absolute/path/to/img-frameright.jpg'
     *               * 'basename':  'img-frameright.jpg'
     *               * 'dirname':   '/absolute/path/to'
     *               * 'name':      'img-frameright'
     *               * 'extension': 'jpg'
     */
    public function unique_target_file($source_path, $basename_suffix) {
        $source_basename = basename($source_path);
        $source_dirname = dirname($source_path);

        $source_basename_items = self::basename_to_name_and_extension(
            $source_basename
        );
        $source_name = $source_basename_items[0];
        $source_extension = $source_basename_items[1];

        $target_name = $source_name . $basename_suffix;
        $target_basename = self::name_and_extension_to_basename(
            $target_name,
            $source_extension
        );

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
     * Returns the title of an image based on its metadata.
     *
     * @param string $path Absolute path of the image file, e.g.
     *                     '/absolute/path/to/img.jpg'.
     * @return string The name of the image ('img') if no title could be found
     *                in the metadata.
     */
    public function image_title($path) {
        $title = $this->global_functions->wp_read_image_metadata($path)[
            'title'
        ];
        if ($title) {
            return $title;
        }

        return self::basename_to_name_and_extension(basename($path))[0];
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

        $extension =
            count($basename_items) >= 2 ? array_pop($basename_items) : '';
        $name = implode(self::EXTENSION_SEPARATOR, $basename_items);

        return [$name, $extension];
    }

    /**
     * Joins file name and extension into a basename.
     *
     * @param string $name      File name, e.g. 'myfile'.
     * @param string $extension File extension, e.g. 'jpg'.
     * @return string 'myfile.jpg'.
     */
    private static function name_and_extension_to_basename($name, $extension) {
        $basename = $name;
        if (!empty($extension)) {
            $basename .= self::EXTENSION_SEPARATOR . $extension;
        }
        return $basename;
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
