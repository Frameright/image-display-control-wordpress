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
        Debug\log_all_fired_hooks();
    }
};
