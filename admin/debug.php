<?php
/**
 * Debugging utils.
 *
 * @package Frameright\Admin\Debug
 */

namespace Frameright\Admin\Debug;

/**
 * Log $text to debug.log if WP_DEBUG is true.
 *
 * @param string $text Text to be logged.
 */
function log( $text ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[frameright] ' . $text );
    }
}

/**
 * Log all action names.
 */
function log_all_fired_actions() {

    /**
     * Log the current action name.
     */
    $log_action = function() {
        log( 'Running action ' . current_action() );
    };

    add_action( 'all', $log_action );

}
