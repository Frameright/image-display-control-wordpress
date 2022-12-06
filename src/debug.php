<?php
/**
 * Debugging utils.
 *
 * @package FramerightImageDisplayControl\Debug
 */

namespace FramerightImageDisplayControl\Debug;

/**
 * Test whether debugging is enabled or not.
 *
 * @return boolean WP_DEBUG
 */
function enabled() {
    return defined('WP_DEBUG') && WP_DEBUG;
}

/**
 * Log $text to debug.log if debugging is enabled.
 *
 * @param string $text Text to be logged.
 */
function log($text) {
    if (enabled()) {
        error_log('[frameright] ' . $text);
    }
}

/**
 * Log all hook names.
 */
function log_all_fired_hooks() {
    add_action('all', function () {
        log('Running hook ' . current_action());
    });
}

/**
 * Log error if $condition isn't truthy. Doesn't interrupt execution on
 * WordPress. Fails unit tests.
 *
 * @param bool   $condition Condition to be asserted.
 * @param string $description Human-readable description of the failed
 *                            expectation.
 * @throws AssertionError If $condition is falsy during unit test execution.
 */
function assert_($condition, $description) {
    if (!$condition) {
        $exception = new AssertionError($description);
        log(
            "Assertion failed: $description" .
                PHP_EOL .
                $exception->getTraceAsString()
        );

        if (!defined('WP_HOME')) {
            // We're not being run on WordPress. We're probably being run by
            // PHPUnit.
            throw $exception;
        }
    }
}

/**
 * AssertionError exists already in PHP 7 but not in PHP 5.
 */
class AssertionError extends \Exception {
}
