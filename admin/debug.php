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

/**
 * Log error if $condition isn't truthy. Doesn't interrupt execution.
 *
 * @param bool   $condition Condition to be asserted.
 * @param string $description Human-readable description of the expectation.
 */
function assert_( $condition, $description ) {
    if ( ! $condition ) {
        log(
            "Assertion failed: $description" . PHP_EOL
            . ( new AssertionError() )->getTraceAsString()
        );
    }
}

/**
 * AssertionError exists already in PHP 7 but not in PHP 5.
 */
class AssertionError extends \Exception {};
