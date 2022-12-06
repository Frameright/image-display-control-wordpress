<?php
/**
 * Dummy file to make intelephense (PHP linter in VSCode) happy. Will never
 * be actually executed nor imported. Workaround for issues like
 *   * https://github.com/bmewburn/vscode-intelephense/issues/568
 *   * https://github.com/bmewburn/vscode-intelephense/issues/952
 *   * https://github.com/bmewburn/vscode-intelephense/issues/1042
 *   * https://github.com/bmewburn/vscode-intelephense/issues/1045
 *
 * The idea is to avoid false intelephense errors on lines like
 *     if (defined('WP_DEBUG') && WP_DEBUG) {
 *
 * @package FramerightImageDisplayControl\Intelephense
 */

// makes PHP_CodeSniffer happy:
if (true || false) {
    throw new \Exception('Do not import nor run me');
}

/**
 * See https://wordpress.org/support/article/debugging-in-wordpress/
 */
define('WP_DEBUG', false);

/**
 * See https://developer.wordpress.org/reference/classes/wp_filesystem_base/abspath/
 */
define('ABSPATH', '');

/**
 * See https://developer.wordpress.org/reference/functions/add_action/
 */
function add_action() {
}

/**
 * See https://developer.wordpress.org/reference/functions/current_action/
 */
function current_action() {
}
