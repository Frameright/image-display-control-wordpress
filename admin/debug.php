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
 * Log all hook names.
 */
function log_all_fired_hooks() {
    add_action(
        'all',
        function() {
            log( 'Running hook ' . current_action() );
        }
    );
}
