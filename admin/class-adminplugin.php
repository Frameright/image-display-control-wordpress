<?php
/**
 * Imported only when browsing admin panel.

 * @package Frameright\Admin
 */

namespace Frameright\Admin;

require_once __DIR__ . '/debug.php';

/**
 * Implementation of the plugin when inside the admin panel.
 */
class AdminPlugin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'wp_handle_upload', [ $this, 'handle_upload' ] );
    }

    /**
     * Filter called when an image gets uploaded to the media library.
     *
     * @param array $data Filter input.
     */
    public function handle_upload( $data ) {
        Debug\log( 'An image got uploaded: ' . print_r( $data, true ) );
        return $data;
    }
};
