<?php
/**
 * Mockable wrapper of all global functions.
 *
 * @package Frameright\Admin
 */

namespace Frameright\Admin;

/**
 * Wrap here any global function (e.g. the ones provided by WordPress) called
 * by the plugin implementation while running PHPUnit. And let your PHPUnit
 * test case mock this class.
 */
class GlobalFunctions {
    /**
     * Called whenever someone attempts to call a method on this object.
     *
     * @param string $method_name The name of the global function to call.
     * @param array  $arguments   The arguments to pass to the global function.
     */
    public function __call($method_name, $arguments) {
        // Call the global function having the same name:
        call_user_func_array($method_name, $arguments);
    }
}
