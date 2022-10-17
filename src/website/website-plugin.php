<?php
/**
 * Imported only when browsing as a visitor.
 *
 * @package Frameright\Website
 */

namespace Frameright\Website;

require_once __DIR__ . '/../debug.php';
use Frameright\Debug;
require_once __DIR__ . '/../global-functions.php';
use Frameright\GlobalFunctions;

/**
 * Implementation of the plugin when outside the admin panel.
 */
class WebsitePlugin {
    /**
     * Constructor.
     *
     * @param Mock_stdClass $global_functions_mock Mock of GlobalFunctions if
     *                                             running tests.
     */
    public function __construct($global_functions_mock = null) {
        $this->global_functions = $global_functions_mock
            ? $global_functions_mock
            : new GlobalFunctions();

        Debug\log_all_fired_hooks();
    }

    /**
     * Mockable wrapper for calling global functions.
     *
     * @var GlobalFunctions
     */
    private $global_functions;
}
